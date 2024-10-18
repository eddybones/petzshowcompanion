import React = require('react');
import { ReactElement, useEffect, useState } from 'react';
import styled from 'styled-components';
import Overlay from "./Overlay";

interface PetProfileProps {
    showLoader: boolean,
    hash: string,
    previousHash?: string,
    nextHash?: string,
    close: () => void,
}

const StyledPetProfile = styled.div`
  position: fixed;
  z-index: 10000;
  left: calc(50% - 200px);
  top: 30px; // TODO: Change this to center vertically somehow maybe?
  
  width: 400px;
  height: fit-content;
  background: #fff;
  border: 3px solid #000;
  border-radius: 5px;

  h1 {
    font-size: 20px;
    margin: 0;
    padding: 0;
  }

  #closeDialog, #closeDialog:link, #closeDialog:visited {
    position: absolute;
    top: 5px;
    right: 5px;
    padding: 4px;
    font-size: 14px;
    cursor: pointer;
    border: 1px solid #000;
    border-radius: 3px;
    background: rgb(150, 150, 150);
  }

  #closeDialog:hover {
    background: rgb(100, 100, 100);
  }
`;

interface PetProfileData {
    hash: string,
    pic: string,
    callName?: string,
    showName?: string,
    notes?: string,
    type?: string,
    retired?: boolean,
    sex?: string,
    prefix?: string,
    hexerOrBreeder?: string,
    birthday?: Date,
}

export default function PetProfile(props: PetProfileProps): ReactElement {
    const [petProfileData, setPetProfileData] = useState({} as PetProfileData);

    useEffect(() => {
        fetch(`/community/pet/${props.hash}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);
        })
    }, []);

    return (
        <>
            <Overlay opacity={0.9}/>
            <StyledPetProfile>
                <header>
                    <span id="closeDialog" className="material-symbols-outlined" onClick={props.close}>close</span>
                    <h1>Pet Name...</h1>
                </header>
            </StyledPetProfile>
        </>
    );
}