import React,{Component,useState, useEffect} from 'react';
import * as Icon from 'react-bootstrap-icons';
import {ToastContainer, Toast ,Modal,Button, Form } from 'react-bootstrap';
import DataTable from 'react-data-table-component';
import axios from 'axios';

const Tasks = (props)=> {
    const [toastShow,setToastShow] = useState(false);
    const [toastBg,setToastBg] = useState("");
    const [toastHeader,setToastHeader] = useState("");
    const [toastMessage,setToastMessage] = useState("");
    const [tasks,setTasks] = useState([]);
    const [selectedTask, setSelectedTask] = useState({});
    const [prerequisites,setPrerequisites] = useState([]);
    const [addPrerequisitesOption,setAddPrerequisitesOption] = useState([]);
    const [showPrerequisitesModal, setPrerequisitesModalShow] = useState(false);
    const [showAddPrerequisitesModal, setAddPrerequisitesModalShow] = useState(false);
    const [showAddTaskModal, setAddTaskModalShow] = useState(false);
   
    const selectPrerequisitesRef  = React.createRef();
    const formTaskTitleRef = React.createRef();
    const formTaskTypeRef = React.createRef();
    const formTaskCurrencyRef = React.createRef();
    const formTaskQuantityRef = React.createRef();
    const formTaskCountryRef = React.createRef();

    useEffect(()=>{
        
        getTasks();

    },[]);

    const getTasks = ()=>{
        axios.get(
            `/api/tasks`
        ).then((res)=>{
            setTasks(res.data.tasks);
            setToastShow(true);
            setToastMessage("Processed successfully!");
            setToastBg("success");
            setToastHeader("Success");
        }).catch((e)=>{
            setToastShow(true);
            if (error.response) {
                var message=" ";
                Object.keys(error.response.data.errors).forEach(key => {
                    message+=error.response.data.errors[key]+"\n";
                });
             
            } else if (error.request) {
            
                console.log(error.request);
            } else {
            
                console.log('Error', error.message);
            }
            
            setToastMessage(message);
            setToastBg("danger");
            setToastHeader("Error");
        });
    }


    useEffect(()=>{
        if(selectedTask.id!==undefined){
            let unAvailablePrerequisites = [];
            unAvailablePrerequisites.push(selectedTask.id);

            //console.log(tasks);
           
            tasks.forEach((item)=>{
                if(item.prerequisites.includes(selectedTask.id)){
                    unAvailablePrerequisites.push(item.id);
                } 

            });

            tasks.forEach((item)=>{
                for(let i = 0; i<unAvailablePrerequisites.length ; i++){
                    if(item.prerequisites.includes(unAvailablePrerequisites[i])){
                        unAvailablePrerequisites.push(item.id);
                        break;
                    } 
                }
            });

            selectedTask.prerequisites
            unAvailablePrerequisites.concat(selectedTask.prerequisites);

            let availablePrerequisites = tasks.filter((item)=>{
                return !unAvailablePrerequisites.includes(item.id) && !selectedTask.prerequisites.includes(item.id);
            });

            let availablePrerequisitesOptions = [];
            availablePrerequisites.forEach((item)=>{
                availablePrerequisitesOptions.push(<option key={item.id} value={item.id} >{item.title}</option>);
            });
            
            setAddPrerequisitesOption(availablePrerequisitesOptions);

        }
    },[tasks]);

    useEffect(()=>{        
        if(selectedTask.id!==undefined){

            getTasks();
            const tasksOfPrerequisites = tasks.filter((el)=>{
                return selectedTask.prerequisites.includes(el.id);
            });
            setPrerequisites(tasksOfPrerequisites);
        }

    },[selectedTask]);

    const handlePrerequisitesClose = () => {
        setPrerequisites([]);
        setPrerequisitesModalShow(false);
        setSelectedTask([]);
    };
    const handlePrerequisitesShow = (item) => {
        const tasksOfPrerequisites = tasks.filter((el)=>{
            return item.prerequisites.includes(el.id);
        });
        setPrerequisites(tasksOfPrerequisites);
        setPrerequisitesModalShow(true);
        setSelectedTask(item);
    };

    const handleAddPrerequisitesShow= ()=>{
        setAddPrerequisitesModalShow(true);
    };
    const handleAddPrerequisitesClose= ()=>{
        setAddPrerequisitesModalShow(false);
    };

    const handleAddTaskShow= ()=>{
        setAddTaskModalShow(true);
    };
    const handleAddTaskClose= ()=>{
        setAddTaskModalShow(false);
    };

    const deletePrerequisite = (row)=>{
        axios.post(
            `/api/tasks/delete_prerequisites`,{
                task : selectedTask.id,
                prerequisite : row.id
            }
        ).then((res)=>{
            setToastShow(true);
            setToastMessage("Processed successfully!");
            setToastBg("success");
            setToastHeader("Success");
            setSelectedTask(res.data);
        }).catch((e)=>{
            setToastShow(true);
            if (error.response) {
                var message=" ";
                Object.keys(error.response.data.errors).forEach(key => {
                    message+=error.response.data.errors[key]+"\n";
                });
             
            } else if (error.request) {
            
                console.log(error.request);
            } else {
            
                console.log('Error', error.message);
            }
            
            setToastMessage(message);
            setToastBg("danger");
            setToastHeader("Error");
        });
    }

    const handleAddPrerequisitesSubmit = ()=>{
        axios.post(
            `/api/tasks/add_prerequisites`,{
                task : selectedTask.id,
                prerequisite : selectPrerequisitesRef.current.value
            }
        ).then((res)=>{
            setToastShow(true);
            setToastMessage("Processed successfully!");
            setToastBg("success");
            setToastHeader("Success");
            setSelectedTask(res.data);
            setAddPrerequisitesModalShow(false);
            
        }).catch((e)=>{
            setToastShow(true);
            if (error.response) {
                var message=" ";
                Object.keys(error.response.data.errors).forEach(key => {
                    message+=error.response.data.errors[key]+"\n";
                });
             
            } else if (error.request) {
            
                console.log(error.request);
            } else {
            
                console.log('Error', error.message);
            }
            
            setToastMessage(message);
            setToastBg("danger");
            setToastHeader("Error");
        });
    }

    const formTaskTypeRefOnChange = ()=>{
        //console.log(formTaskTypeRef);
        switch(formTaskTypeRef.current.value) {
            case "common_ops":
                formTaskCurrencyRef.current.disabled=true;
                formTaskQuantityRef.current.disabled=true;
                formTaskCountryRef.current.disabled=true;
                break;
            case "invoice_ops":
                formTaskCurrencyRef.current.disabled=false;
                formTaskQuantityRef.current.disabled=false;
                formTaskCountryRef.current.disabled=true;
                break;
            case "custom_ops":
                formTaskCurrencyRef.current.disabled=true;
                formTaskQuantityRef.current.disabled=true;
                formTaskCountryRef.current.disabled=false;
                break;
            default:
                formTaskCurrencyRef.disabled=true;
                formTaskQuantityRef.current.disabled=true;
                formTaskCountryRef.current.disabled=true;
        }
    }
    const handleAddTaskSubmit = ()=>{
        let formdata = {}
        formdata.title = formTaskTitleRef.current.value;
        formdata.type = formTaskTypeRef.current.value;
        switch(formTaskTypeRef.current.value) {
            
            case "invoice_ops":
                formdata.amount = {};
                formdata.amount.currency = formTaskCurrencyRef.current.value;
                formdata.amount.quantity = formTaskQuantityRef.current.value;
                break;
            case "custom_ops":
                formdata.country = formTaskCountryRef.current.value;
                break;
            default:
                break;
        }

        axios.post(
            `/api/tasks/create_task`,{
                ...formdata
            }
        ).then((res)=>{
            setToastShow(true);
            setToastMessage("Processed successfully!");
            setToastBg("success");
            setToastHeader("Success");

            getTasks();
            setAddTaskModalShow(false);              
        }).catch((error)=>{
            setToastShow(true);
            if (error.response) {
                var message=" ";
                Object.keys(error.response.data.errors).forEach(key => {
                    message+=error.response.data.errors[key]+"\n";
                });
             
            } else if (error.request) {
            
                console.log(error.request);
            } else {
            
                console.log('Error', error.message);
            }
            
            setToastMessage(message);
            setToastBg("danger");
            setToastHeader("Error");
        });

    }
    
    return(
        <div className="d-flex flex-column justify-content-center">
            <ToastContainer position={'middle-center'} style={{zIndex:9999}} >
                <Toast 
                    bg={toastBg} 
                    show={toastShow} 
                    autohide={true}
                    onClose = {()=>{setToastShow(false)}}
                >
                    <Toast.Header>
                        <strong className="me-auto">{toastHeader}</strong>
                    </Toast.Header>
                    <Toast.Body>{toastMessage}</Toast.Body>
                </Toast>
            </ToastContainer>

            <Modal show={showAddTaskModal} onHide={handleAddTaskClose}>
                <Modal.Header closeButton>
                <Modal.Title>Task</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        <Form.Group className="mb-3" controlId="formTaskTitle">
                            <Form.Label>Title</Form.Label>
                            <Form.Control ref={formTaskTitleRef} >
                            </Form.Control>
                        </Form.Group>
                        <Form.Group className="mb-3" controlId="formTaskType">
                            <Form.Label>Type</Form.Label>
                            <Form.Select 
                                ref={formTaskTypeRef}  
                                onChange={()=>{formTaskTypeRefOnChange()}}
                            >
                                <option value="common_ops" >common_ops</option>
                                <option value="invoice_ops" >invoice_ops</option>
                                <option value="custom_ops" >custom_ops</option>
                            </Form.Select>
                        </Form.Group>
                        <Form.Group className="mb-3" controlId="formCurrencyType">
                            <Form.Label>Amount Currency</Form.Label>
                            <Form.Select ref={formTaskCurrencyRef} disabled >
                                <option value="₺" >₺</option>
                                <option value="€" >€</option>
                                <option value="$" >$</option>
                                <option value="£" >£</option>
                            </Form.Select>
                        </Form.Group>
                        <Form.Group className="mb-3" controlId="formTaskQuantity">
                            <Form.Label>Amount Quantity</Form.Label>
                            <Form.Control ref={formTaskQuantityRef} disabled >
                            </Form.Control>
                        </Form.Group>
                        <Form.Group className="mb-3" controlId="formTaskCountry">
                            <Form.Label>Country</Form.Label>
                            <Form.Control ref={formTaskCountryRef} disabled >
                            </Form.Control>
                        </Form.Group>

                        <Button 
                            variant="primary" 
                            type="button"
                            onClick={()=>{handleAddTaskSubmit();}} 
                        >
                            Submit
                        </Button>
                    </Form>
                </Modal.Body>
            </Modal>
            
            <Modal show={showAddPrerequisitesModal} onHide={handleAddPrerequisitesClose}>
                <Modal.Header closeButton>
                <Modal.Title>Prerequisites</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        <Form.Group className="mb-3" controlId="addPrerequisitesForm">
                            <Form.Label>Available Prerequisites</Form.Label>
                            <Form.Select ref={selectPrerequisitesRef} >
                                {addPrerequisitesOption}
                            </Form.Select>
                        </Form.Group>

                        <Button 
                            variant="primary" 
                            type="button"
                            onClick={()=>{handleAddPrerequisitesSubmit();}} 
                        >
                            Submit
                        </Button>
                    </Form>
                </Modal.Body>
            </Modal>
            
            <Modal show={showPrerequisitesModal} onHide={handlePrerequisitesClose}>
                <Modal.Header closeButton>
                <Modal.Title>Prerequisites</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                <DataTable 
                    subHeader={false}
                    responsive={true}
                    hover={true}
                    title={<button 
                        onClick={()=>{handleAddPrerequisitesShow();}} 
                        className={"btn btn-success"} 
                        >
                            <Icon.PlusCircle /> Add
                        </button>}
                    fixedHeader
                    pagination
                    data={prerequisites}
                    columns={[
                        {
                            name:"Id",
                            selector:row=>row.id
                        },
                        {
                            name:"Title",
                            selector:row=>row.title
                        },
                        {
                            name:"Type",
                            selector:row=>row.type
                        },
                      
                        {
                            name:"Delete",
                            cell:(row)=><button 
                            onClick={()=>{deletePrerequisite(row)}} 
                            className={"btn btn-danger"} 
                            >
                                <Icon.TrashFill />
                            </button>
                        }
                    ]}

                />

                </Modal.Body>
            </Modal>



            <DataTable 
                subHeader={false}
                responsive={true}
                hover={true}
                fixedHeader
                pagination
                title={<button 
                    onClick={()=>{handleAddTaskShow();}} 
                    className={"btn btn-success"} 
                    >
                        <Icon.PlusCircle /> Add New Task
                    </button>}
                columns={[
                    {
                        name:"Order",
                        selector:(row,index)=>index+1
                    },
                    {
                        name:"Id",
                        selector:row=>row.id
                    },
                    {
                        name:"Prerequisites",
                        selector:row=>row.prerequisites.toString()
                    },
                    {
                        name:"Title",
                        selector:row=>row.title
                    },
                    {
                        name:"Type",
                        selector:row=>row.type
                    },
                    {
                        name:"Country",
                        selector:row=>row.country||""
                    },
                    {
                        name:"Currency",
                        selector:row=>row.amount?row.amount.currency||"":""
                    },
                    {
                        name:"Quantity",
                        selector:row=>row.amount?row.amount.quantity||"":""
                    },
                    {
                        name:"Prerequisites",
                        cell:(row)=><button 
                            onClick={()=>{handlePrerequisitesShow(row)}} 
                            className={"btn btn-warning"} 
                        >
                            <Icon.PlusCircle />
                        </button>
                    },
                    {
                        name:"Done",
                        cell:(row)=><button className={"btn btn-success"} ><Icon.CheckCircle /></button>
                    },
                    {
                        name:"Edit",
                        cell:(row)=><button className={"btn btn-primary"} ><Icon.PencilSquare /></button>
                    },
                    {
                        name:"Delete",
                        cell:(row)=><button className={"btn btn-danger"} ><Icon.TrashFill /></button>
                    }
                ]}
                data={tasks}
            />
        </div>
    );
}

export default Tasks;