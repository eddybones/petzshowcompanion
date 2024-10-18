import React = require('react');
import { createRoot } from 'react-dom/client';
import PointsDialog from './component/PointsDialog';

declare global {
    interface Window {
        useCompactView: boolean,
        petz: [],
    }
}

export const editPointsEvent = (e) => {
    e.preventDefault();
    const hash = (e.target as HTMLElement).dataset.hash;

    const root = createRoot(document.getElementById('app'));
    root.render(
        <PointsDialog useCompactView={window.useCompactView} hash={hash} close={() => root.unmount()}/>
    );
}

const attachEditPointsEvent = (): void => {
    document.querySelectorAll('.editPoints').forEach(element => element.addEventListener('click', event => editPointsEvent(event)));
}

attachEditPointsEvent();