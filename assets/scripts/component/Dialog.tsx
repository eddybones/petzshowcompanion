import React = require('react');
import { ReactElement } from 'react';
import styled from 'styled-components';
import Loader from './Loader';
import Overlay from "./Overlay";

interface DialogProps {
    showLoader: boolean,
    content: ReactElement,
    close: () => void,

    width?: number,
    height?: number,
    title?: string,
    error?: boolean,
}

const StyledDialog = styled.div<{ $width?: number, $height?: number }>`
  position: fixed;
  z-index: 10000;
  left: calc(50% - 200px);
  top: 10px;

  width: ${props => props.$width ? props.$width + 'px' : 'fit-content'};
  min-width: 200px;
  min-height: ${props => props.$height || 150}px;
  height: fit-content;
  background: #fff;
  border: 3px solid #000;
  border-radius: 5px;

  & > header {
    position: relative;
    background: #eee;
    padding: 8px;

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
  }

  & > section {
    width: 100%;
    padding: 8px;
  }
`;

export default function Dialog(props: DialogProps): ReactElement {
    return (
        <>
            <Overlay/>
            <StyledDialog $width={props.width} $height={props.height}>
                <header>
                    <span id="closeDialog" className="material-symbols-outlined" onClick={props.close}>close</span>
                    {props.title ? <h1>{props.title}</h1> : <></>}
                </header>
                <section>
                    {props.error ? props.error : <></>}
                    {!props.error && props.showLoader ? <Loader/> : <></>}
                    {!props.error && !props.showLoader && props.content}
                </section>
            </StyledDialog>
        </>
    );
}