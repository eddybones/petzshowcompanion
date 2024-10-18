import React = require('react');
import styled from 'styled-components';
import { ReactElement, useRef, useState } from 'react';
import { User } from './Users';
import Loader from '../Loader';
import PetTile from './PetTile';
import { Pet } from '../../common/Interfaces';

const StyledUserRow = styled.details`
  border: 1px solid #000000;
  border-radius: 6px;
  margin-bottom: 4px;
  width: 100%;
  overflow: hidden;
  
  summary {
    background: #eee;
    padding: 6px;
    cursor: pointer;
    
    span {
      display: inline-block;
    }
    
    .userId {
      width: 75px;
      text-align: center;
      color: rgb(175, 175, 175);
    }
    .userId:before {
      content: '[id:';
    }
    .userId:after {
      content: ']';
    }
    
    .userEmail {
      width: 220px;
      overflow: clip;
      text-overflow: ellipsis;
    }
    
    .userAdded {
      width: 150px;
      text-align: center;
    }
    
    .userPetz {
      width: 100px;
    }
  }
  
  .content {
    position: relative;
    min-height: 50px;
    padding: 16px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
  }
`;

interface UserRowProps {
    user: User,
}

export default function UserRow(props: UserRowProps): ReactElement {
    const [showLoader, setShowLoader] = useState(false);
    const [error, setError] = useState(null);
    const petz = useRef(null);

    return (
        <StyledUserRow onToggle={(e) => {
            if(!petz.current) {
                setShowLoader(true);
                fetch(`/api/petz/${props.user.id}`)
                    .then((response) => response.json())
                    .then((data) => {
                        petz.current = data as Pet[];
                    })
                    .catch(() => setError(<p>Error getting petz.</p>))
                    .finally(() => setShowLoader(false));
            }
        }}>
            <summary>
                <span className="userId">{props.user.id}</span>
                <span className="userEmail">{props.user.email}</span>
                <span className="userAdded">{props.user.dateAdded}</span>
                <span className="userPetz">({props.user.totalPetz})</span>
            </summary>
            <div className="content">
                {showLoader && <Loader small={true}/>}
                {!showLoader && error && error}
                {!showLoader && !error && petz.current && petz.current.map(pet => <PetTile pet={pet} key={pet.hash}/>)}
            </div>
        </StyledUserRow>
    );
}