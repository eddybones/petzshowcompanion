import React = require('react');
import { useEffect, useRef, useState } from 'react';
import Loader from '../Loader';
import UserRow from './UserRow';
import styled from 'styled-components';

const StyledUsers = styled.div`
  width: 100%;
`;

export interface User {
    id: number,
    email: string,
    dateAdded: string,
    totalPetz: number,
}

export default function Users() {
    const users = useRef(null);
    const [error, setError] = useState(null);
    const [showLoader, setShowLoader] = useState(true);

    useEffect(() => {
        if(!users.current) {
            fetch('/api/users')
                .then((response) => response.json())
                .then((data) => {
                    users.current = data;
                })
                .catch(() => setError(<p>Error getting users.</p>))
                .finally(() => setShowLoader(false));
        }
    }, []);

    return (
        <StyledUsers>
            {error && error}
            {!error && showLoader && <Loader/>}
            {!error && !showLoader &&
                <>
                    <h2>Users</h2>
                    {users.current.map((user) => <UserRow user={user} key={user.id} />)}
                </>
            }
        </StyledUsers>
    );
}