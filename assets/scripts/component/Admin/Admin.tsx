import React = require("react");
import { ReactElement } from 'react';
import styled from 'styled-components';
import { Routing } from '../../routing/routing';
import { Link, Outlet } from 'react-router-dom';

const StyledAdmin = styled.div`
  position: relative;
  display: flex;
`;

export default function Admin(): ReactElement {
    return (
        <StyledAdmin>
            <nav>
                <h2>Admin</h2>
                <Link id="top" to={Routing.ListUsers}>Users</Link>
            </nav>
            <Outlet />
        </StyledAdmin>
    );
};