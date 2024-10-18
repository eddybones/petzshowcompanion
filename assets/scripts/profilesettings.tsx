import React = require('react');
import { createRoot } from 'react-dom/client';
import ProfileSettings from './component/ProfileSettings';

const container = document.getElementById('profile');
if(container) {
    createRoot(container).render(<ProfileSettings/>);
}