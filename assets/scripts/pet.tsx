import React = require('react');
import { createRoot } from 'react-dom/client';
import { PicForm } from './component/Pics';

const tagContainer: HTMLElement = document.getElementById('picForm');
const hash = (document.getElementById('petHash') as HTMLInputElement).value;
if(tagContainer) {
    createRoot(tagContainer).render(<PicForm hash={hash} />);
}