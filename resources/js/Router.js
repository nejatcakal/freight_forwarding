import React from 'react'
import  {Route, Routes} from 'react-router-dom'

/* pages */
import FrontTasks from './views/tasks';

const Main = () => (
    <Routes>
        <Route exact path="/"  element={<FrontTasks/>} />
    </Routes>
);

export default Main;
