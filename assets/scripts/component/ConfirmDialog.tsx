import React = require('react');
import styled from 'styled-components';
import { ReactElement, useEffect, useRef } from 'react';
import Loader from './Loader';

interface ConfirmDialogProps {
    isOpen: boolean,
    showLoader: boolean,
    message: string,
    onYes: () => void,
    onNo: () => void,
}

const StyledConfirmDialog = styled.dialog`
  border-radius: 6px;
  padding: 6px;
  min-height: 40px;
  min-width: 40px;

  button {
    margin-right: 10px;
  }

  &::backdrop {
    background: rgba(0, 0, 0, 0.5);
  }
`;

export default function ConfirmDialog(props: ConfirmDialogProps): ReactElement {
    const dialogRef = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        if(props.isOpen) {
            dialogRef.current.showModal();
        } else {
            dialogRef.current.close();
        }
    }, [props.isOpen]);

    return (
        <StyledConfirmDialog id="confirmDialog" ref={dialogRef}>
            {props.showLoader
                ? <Loader small={true}/>
                : <>
                    <p>{props.message}</p>
                    <button id="confirmYes" type="button" onClick={props.onYes}>Yes</button>
                    <button id="confirmNo" type="button" onClick={props.onNo}>No</button>
                </>}
        </StyledConfirmDialog>
    );
}