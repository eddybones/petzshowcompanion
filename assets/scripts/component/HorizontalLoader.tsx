import React = require('react');
import styled from 'styled-components';

const StyledHorizontalLoader = styled.div<{ $size?: string }>`
  box-sizing: border-box;
  display: inline-block;
  position: relative;
  width: ${props => props.$size == 'small' ? 40 : props.$size == 'smallest' ? 20 : 80 }px;
  height: ${props => props.$size == 'small' ? 40 : props.$size == 'smallest' ? 20 : 80 }px;
  
  & > div {
    box-sizing: border-box;
    position: absolute;
    top: ${props => props.$size == 'small' ? 15.33 : props.$size == 'smallest' ? 7.5 : 33.33 }px;
    width: ${props => props.$size == 'small' ? 6.33 : props.$size == 'smallest' ? 3.5 : 13.33 }px;
    height: ${props => props.$size == 'small' ? 6.33: props.$size == 'smallest' ? 3.5 : 13.33 }px;
    border-radius: 50%;
    background: currentColor;
    animation-timing-function: cubic-bezier(0, 1, 1, 0);
  }

  & > div:nth-child(1) {
    left: ${props => props.$size == 'small' ? 4 : props.$size == 'smallest' ? 2 : 8 }px;
    animation: lds-ellipsis1 0.6s infinite;
  }

  & > div:nth-child(2) {
    left: ${props => props.$size == 'small' ? 4 : props.$size == 'smallest' ? 2 : 8 }px;
    animation: lds-ellipsis2 0.6s infinite;
  }

  & > div:nth-child(3) {
    left: ${props => props.$size == 'small' ? 16 : props.$size == 'smallest' ? 8 : 32 }px;
    animation: lds-ellipsis2 0.6s infinite;
  }

  & > div:nth-child(4) {
    left: ${props => props.$size == 'small' ? 28 : props.$size == 'smallest' ? 14 : 56 }px;
    animation: lds-ellipsis3 0.6s infinite;
  }

  @keyframes lds-ellipsis1 {
    0% {
      transform: scale(0);
    }
    100% {
      transform: scale(1);
    }
  }
  @keyframes lds-ellipsis3 {
    0% {
      transform: scale(1);
    }
    100% {
      transform: scale(0);
    }
  }
  @keyframes lds-ellipsis2 {
    0% {
      transform: translate(0, 0);
    }
    100% {
      transform: translate(${props => props.$size == 'small' ? 12 : props.$size == 'smallest' ? 6 : 24 }px, 0);
    }
  }
`;

interface HorizontalLoaderProps {
    small?: boolean,
    smallest?: boolean,
}

export default function Loader(props: HorizontalLoaderProps) {
    return (
        <StyledHorizontalLoader $size={props.small ? 'small' : props.smallest ? 'smallest' : 'default'} className="horizontalLoader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </StyledHorizontalLoader>
    );
}