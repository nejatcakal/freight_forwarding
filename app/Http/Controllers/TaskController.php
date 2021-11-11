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

    public function refreshJsonData(){
        $this->json_file_path  = storage_path('app/storage.json');
        $this->json_data = json_decode(file_get_contents($this->json_file_path), true);
        
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createTask(Request $request)
    {
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
   
        $new_json_data = $request->only([
            "title","type","amount.currency","amount.quantity","country"
        ]);

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

        $new_json_data["id"]=$this->createId();
        $new_json_data["prerequisites"]=[];
        $this->json_data["tasks"][]= $new_json_data ;
        $new_json_string=json_encode($this->json_data,JSON_PRETTY_PRINT);

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
        $end_of_data = end($this->json_data["tasks"]);
        $new_id="task_".explode("_",$end_of_data["id"])[1]+1;
        return $new_id;
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deletePrerequisitesFromTask(Request $request)
    {
       
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


        $theTask = array_values(array_filter(
            $newjsonData,
            function($item)use($request){
                return $item["id"]==$request->task;
            }
        ))[0];   
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
        $tasks_ids = array_map(function($item){
            return $item["id"];
        },$this->json_data["tasks"]);

        $validatorTask = Validator::make(
            $request->all(),
            [
                'task'=> 'required|string|in:'.implode(",",$tasks_ids),
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


       
        $newjsonData =  array_map(function($item)use($request){
            if($item["id"]==$request->task){
                $item["prerequisites"][] = $request->prerequisite;
            }
            return $item;
        },$this->json_data["tasks"]);


        $theTask = array_values(array_filter(
            $newjsonData,
            function($item)use($request){
                return $item["id"]==$request->task;
            }
        ))[0];   
        $new_json_string=json_encode(["tasks"=>$newjsonData],JSON_PRETTY_PRINT);
        file_put_contents($this->json_file_path, stripslashes($new_json_string));
        $this->refreshJsonData();
        return response()->json($theTask);

    }
}
