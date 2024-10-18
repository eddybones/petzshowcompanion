import React = require('react');
import { ReactElement, useState } from 'react';
import { Pet } from '../../common/Interfaces';
import styled from 'styled-components';
import PetDetailsDialog from './PetDetailsDialog';

const StyledPetTile = styled.div`
  width: 200px;
  border: 1px solid #000000;
  border-radius: 6px;
  overflow: hidden;
  padding: 6px;
  margin: 0 10px 10px 0;
  text-align: center;
  background: #eeeeee;

  .pic {
    display: flex;
    justify-content: center;
    width: 154px;
    height: 151px;
    min-width: 154px;
    min-height: 151px;
    background: #ffffff;
    box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.4);
    border-radius: 4px;
    padding: 2px;
    margin: 0 auto;

    img {
      align-self: center;
      max-width: 100%;
      max-height: 100%;
    }
  }
  
  .details {
    margin-top: 10px;
  }
`;

interface PetTileProps {
    pet: Pet,
}

const silhouette = require('../../../images/default_pic.png');

export default function PetTile(props: PetTileProps): ReactElement {
    const [showDialog, setShowDialog] = useState(false);

    return (
        <StyledPetTile>
            {showDialog && <PetDetailsDialog close={() => setShowDialog(false)} pet={props.pet}/>}
            <div className="pic">
                {props.pet.pics
                    ? <img src={`/pics/${props.pet.pics}`} alt={`pic of ${props.pet.callName}`}/>
                    : <img src={silhouette} alt="silhouette"/>
                }
            </div>
            <div className="details">
                <a href="#" onClick={(e) => {
                    e.preventDefault();
                    setShowDialog(true);
                }}>{props.pet.callName}</a>
            </div>
        </StyledPetTile>
    );
}