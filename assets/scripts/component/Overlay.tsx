import React = require('react');
import { ReactElement } from 'react';
import styled from 'styled-components';

interface OverlayProps {
    opacity?: number,
}

const StyledOverlay = styled.div<{ $opacity?: number }>`
  position: fixed;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  background: rgba(0, 0, 0, ${props => props.$opacity || 0.7});
  z-index: 9999;
`;

export default function Overlay(props: OverlayProps): ReactElement {
    return <StyledOverlay $opacity={props.opacity ?? 0.7}/>;
}