<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    private $json_data;
    private $json_file_path;
    public function __construct(){
        $this->refreshJsonData();
    }

    /*
        Sorting tasks
    */
    public function refreshJsonData(){
        $this->json_file_path  = storage_path('app/storage.json');
        $this->json_data = json_decode(file_get_contents($this->json_file_path), true);
        // sorting tasks
        usort($this->json_data["tasks"],function ($a,$b){
            if (in_array($a["id"],$b["prerequisites"])){
                return -1;
            }else{
                return 1;
            }
        
        });

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json($this->json_data);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createTask(Request $request)
    {
        // check all required fills
        $validator = Validator::make(
            $request->all(),
            [
                'title'=> 'required|string',
                'type'=> 'required|string|in:common_ops,invoice_ops,custom_ops',
                'amount' => 'required_if:type,invoice_ops|array',
                'amount.currency' =>'required_if:type,invoice_ops|string|in:₺,€,$,£',
                'amount.quantity' =>'required_if:type,invoice_ops|numeric',
                'country' => 'required_if:type,custom_ops|string',
                //'prerequisites' => 'required|array'
            ]
        );

        if($validator->fails()){
            return response()->json(
                [
                    'status' => false,
                    'errors' => $validator->errors()
                ],
                400
            );
        }
        
        // get only useable fills
        $new_json_data = $request->only([
            "title","type","amount.currency","amount.quantity","country"
        ]);

        // control the task types and add take require fills 
        switch ($request->type) {
            case "common_ops":
                unset($new_json_data["amount"]);
                unset($new_json_data["country"]);
                break;
            case "invoice_ops":
                unset($new_json_data["country"]);
                break;
            case "custom_ops":
                unset($new_json_data["amount"]);
                break;
        }
        //create task id
        $new_json_data["id"]=$this->createId();
        //create prerequisites array
        $new_json_data["prerequisites"]=[];
        //add the task to json_data
        $this->json_data["tasks"][]= $new_json_data ;
        $new_json_string=json_encode($this->json_data,JSON_PRETTY_PRINT);
        //rebuild json_data
        file_put_contents($this->json_file_path, stripslashes($new_json_string));
        $this->refreshJsonData();
        return response()->json($request);
        
    }



    /**
     * create task id.
     *
     * @return numeric
     */
    public function createId()
    {
        // create new id
        if(!empty($this->json_data["tasks"])){
            $end_of_data = end($this->json_data["tasks"]);
            $new_id="task_".explode("_",$end_of_data["id"])[1]+1;
        }else{
            $new_id=0;
        }
        return $new_id;
    }

   

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteTask(Request $request)
    {
        $prerequisites_ids = [];
        $tasks_ids = [];
        // take task id for existing
        // take prerequiaites task id for existing in prerequisites
        foreach($this->json_data["tasks"] as $key=>$item){
            $prerequisites_ids = array_merge($prerequisites_ids,$item["prerequisites"]);
            $tasks_ids[] = $item["id"];
        }
        // check existing task_id and not in prerequisites
        $validatorTask = Validator::make(
            $request->all(),
            [
                'task'=> 'required|string|in:'.implode(",",$tasks_ids).'|not-in:'.implode(",",$prerequisites_ids),
            ]
        );
       
        if($validatorTask->fails()){
            return response()->json(
                [
                    'status' => false,
                    'errors' => $validatorTask->errors()
                ],
                400
            );
        }

        
        // check existing task_id in prerequisites
        /*
        if(in_array($request->task,$prerequisites_ids)){
            return response()->json(
                [
                    'status' => false,
                    'errors' => ["this task cannot be deleted!"]
                ],
                400
            );
        }
        */
        // remove from json_data the task
        foreach($this->json_data["tasks"] as $key=>$item){
            if($item["id"]==$request->task ){
                unset($this->json_data["tasks"][$key]);
            }
        }
        
        // build new json_data 
        $new_json_string=json_encode($this->json_data,JSON_PRETTY_PRINT);
        file_put_contents($this->json_file_path, stripslashes($new_json_string));
        $this->refreshJsonData();
        return response()->json(
            [
                'status' => true,
                'message' => ""
            ],
            200
        );
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deletePrerequisitesFromTask(Request $request)
    {
       //remove from prerequsites the task
        $newjsonData =  array_map(function($item)use($request){
            if($item["id"]==$request->task){
                foreach($item["prerequisites"] as $key => $val){
                    if($val==$request->prerequisite){
                        unset($item["prerequisites"][$key]);
                    } 
                }
            }
            return $item;
        },$this->json_data["tasks"]);

        // get  task after deletion
        $theTask = array_values(array_filter(
            $newjsonData,
            function($item)use($request){
                return $item["id"]==$request->task;
            }
        ))[0];   
        //rebuild json_data
        $new_json_string=json_encode(["tasks"=>$newjsonData],JSON_PRETTY_PRINT);
        file_put_contents($this->json_file_path, stripslashes($new_json_string));
        $this->refreshJsonData();
        return response()->json($theTask);

    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addPrerequisitesToTask(Request $request)
    {
        // take task id for existing
        $tasks_ids = array_map(function($item){
            return $item["id"];
        },$this->json_data["tasks"]);


        //take unusiable tasks
        $unAvailablePrerequisites = [];
        $unAvailablePrerequisites[]= $request->task;
        foreach($this->json_data["tasks"] as $item){
            if($request->task==$item["id"]){
                $unAvailablePrerequisites=array_merge($unAvailablePrerequisites,$item["prerequisites"]);
            }
            if(in_array($request->task,$item["prerequisites"])){
                $unAvailablePrerequisites[]= $item["id"];
            }
        }

        foreach($this->json_data["tasks"] as $item){
            $result = array_intersect($unAvailablePrerequisites,$item["prerequisites"]);
            if(count($result)>0){
                $unAvailablePrerequisites[]= $item["id"];
            }
        }

        // check existing task_id and check the task npt in unuseable tasks
        $validatorTask = Validator::make(
            $request->all(),
            [
                'task'=> 'required|string|in:'.implode(",",$tasks_ids),
                'prerequisite' =>'required|string|not-in:'.implode(",",$unAvailablePrerequisites),
            ]
        );

        if($validatorTask->fails()){
            return response()->json(
                [
                    'status' => false,
                    'errors' => $validatorTask->errors()
                ],
                400
            );
        }
        

        // check the task in  unusiable tasks
        /*
        $validatorPrerequisites = Validator::make(
            $request->all(),
            [
                'prerequisite' =>'required|string|not-in:'.implode(",",$unAvailablePrerequisites),
                
            ]
        );

        if($validatorPrerequisites->fails()){
            return response()->json(
                [
                    'status' => false,
                    'errors' => $validatorPrerequisites->errors()
                ],
                400
            );
        }
        */

        // build new json_data 
        $newjsonData =  array_map(function($item)use($request){
            if($item["id"]==$request->task){
                $item["prerequisites"][] = $request->prerequisite;
            }
            return $item;
        },$this->json_data["tasks"]);

        // get the task after addition
        $theTask = array_values(array_filter(
            $newjsonData,
            function($item)use($request){
                return $item["id"]==$request->task;
            }
        ))[0];   
        // rebuild json_data
        $new_json_string=json_encode(["tasks"=>$newjsonData],JSON_PRETTY_PRINT);
        file_put_contents($this->json_file_path, stripslashes($new_json_string));
        $this->refreshJsonData();
        return response()->json($theTask);

    }
}
