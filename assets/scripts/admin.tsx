import React = require('react');
import { createRoot } from 'react-dom/client';
import Admin from './component/Admin/Admin';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { Routing } from './routing/routing';
import ErrorPage from './component/Admin/ErrorPage';
import Users from './component/Admin/Users';

const router = createBrowserRouter([
    {
        path: Routing.Root,
        element: <Admin/>,
        errorElement: <ErrorPage/>, // TODO: Figure out why this doesn't work
        children: [
            {
                path: Routing.ListUsers,
                element: <Users />
            }
        ]
    },
]);

createRoot(document.getElementById('admin')).render(
    <React.StrictMode>
        <RouterProvider router={router}/>
    </React.StrictMode>
);