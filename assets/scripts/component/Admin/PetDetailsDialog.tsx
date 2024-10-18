import React = require('react');
import { ReactElement } from 'react';
import { Pet } from '../../common/Interfaces';
import Dialog from '../Dialog';
import styled from 'styled-components';

interface PetDetailsDialogProps {
    pet: Pet,
    close: () => void,
}

const StyledContent = styled.div`
  text-align: left;

  .field {
    font-weight: bold;
  }

  .field:after {
    content: ':';
  }

  .notes {
    display: block;
    border: 1px solid #999999;
    width: 90%;
    padding: 10px;
  }
`;

export default function PetDetailsDialog(props: PetDetailsDialogProps): ReactElement {
    return (<Dialog close={props.close} title={props.pet.callName} showLoader={false} width={400} content={
        <StyledContent>
            <p><span className="field">Added</span> <span className="fieldValue">{props.pet.addedOn}</span></p>
            {props.pet.showName &&
                <p><span className="field">Show Name</span> <span className="fieldValue">{props.pet.showName}</span>
                </p>}
            {props.pet.birthday &&
                <p><span className="field">Birthday</span> <span className="fieldValue">{props.pet.birthday}</span></p>}
            {props.pet.hexerOrBreeder && <p><span className="field">Hexer/Breeder</span> <span
                className="fieldValue">{props.pet.hexerOrBreeder}</span></p>}
            {props.pet.prefix &&
                <p><span className="field">Prefix</span> <span className="fieldValue">{props.pet.prefix}</span></p>}
            {props.pet.sex &&
                <p><span className="field">Sex</span> <span className="fieldValue">{props.pet.sex}</span></p>}
            {props.pet.type &&
                <p><span className="field">Type</span> <span className="fieldValue">{props.pet.type}</span></p>}
            <p><span className="field">Retired</span> <span
                className="fieldValue">{props.pet.retired ? 'Yes' : 'No'}</span></p>
            {props.pet.tags.length > 0 &&
                <>
                    <span className="field">Tags</span>
                    <ul>
                        {props.pet.tags.map(tag => <li key={tag.hash}>{tag.name}</li>)}
                    </ul>
                </>
            }
            {props.pet.rollup.length > 0 &&
                <>
                    <span className="field">Points</span>
                    <ul>
                        {props.pet.rollup.map(point =>
                            <li key={point.showType}>{`${point.showType}: ${point.total}`}</li>)}
                    </ul>
                </>
            }
            {props.pet.notes &&
                <p><span className="field">Notes</span> <span className="fieldValue notes">{props.pet.notes}</span></p>}
            {/* rollup */}
        </StyledContent>
    }/>);
}