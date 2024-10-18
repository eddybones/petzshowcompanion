import React = require('react');
import styled from 'styled-components';
import { ReactElement, useEffect, useRef } from 'react';
import Loader from './Loader';

interface OkDialogProps {
    isOpen: boolean,
    showLoader: boolean,
    message: string,
    onOk: () => void,
}

const StyledOkDialog = styled.dialog`
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

export default function OkDialog(props: OkDialogProps): ReactElement {
    const dialogRef = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        if(props.isOpen) {
            dialogRef.current.showModal();
        } else {
            dialogRef.current.close();
        }
    }, [props.isOpen]);

    return (
        <StyledOkDialog id="okDialog" ref={dialogRef}>
            {props.showLoader
                ? <Loader small={true}/>
                : <>
                    <p>{props.message}</p>
                    <button id="confirmOk" type="button" onClick={props.onOk}>OK</button>
                </>}
        </StyledOkDialog>
    );
}