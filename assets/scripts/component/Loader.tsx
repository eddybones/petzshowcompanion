import React = require('react');
import styled from 'styled-components';

const loaderImg = require('../../images/loader.gif');

const StyledLoader = styled.div<{ $size?: number, $center?: boolean }>`
  position: ${props => props.$center ? 'absolute' : 'relative'};
  top: ${props => props.$center ? 'calc(50% - ' + props.$size / 2 + 'px)' : ''};
  left: ${props => props.$center ? 'calc(50% - ' + props.$size / 2 + 'px)' : ''};
  //top: calc(50% - ${props => props.$size/2}px);
  //left: calc(50% - ${props => props.$size/2}px);
  width: ${props => props.$size}px;
  height: ${props => props.$size}px;
  background: #ffffff url(${loaderImg}) center center no-repeat;
  background-size: ${props => props.$size}px;
`;

interface LoaderProps {
    small?: boolean,
    smallest?: boolean,
    center?: boolean,
}

export default function Loader(props: LoaderProps) {
    let size = 64;
    if(props.small) {
        size = 32;
    }
    if(props.smallest) {
        size = 24;
    }
    let center = props.center ?? true;
    return (<StyledLoader $size={size} $center={center} className="wait"></StyledLoader>);
}