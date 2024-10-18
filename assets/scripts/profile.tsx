import React = require('react');
import { createRoot } from 'react-dom/client';
import PetProfile from './component/PetProfile';

const showProfileOverlay = (clicked: Element) => {
    const container = document.getElementById('profileOverlay');
    if(container) {
        let previousElement = clicked.previousElementSibling;
        let previousHash = null;
        if(previousElement != null) {
            previousHash = previousElement.id;
        }
        let nextElement = clicked.nextElementSibling;
        let nextHash = null;
        if(nextElement != null) {
            nextHash = nextElement.id;
        }
        const root = createRoot(container);
        root.render(<PetProfile previousHash={previousHash} nextHash={nextHash} hash={clicked.id} showLoader={false} close={() => { root.unmount(); }}/>);
    }
}

document.querySelectorAll('.profileGrid').forEach(e => e.addEventListener('click', () => { showProfileOverlay(e); }));
