import React = require('react');
import { createRoot } from 'react-dom/client';
import Tags from './component/Tags';

const tagContainer = document.getElementById('tags');
if(tagContainer) {
    createRoot(tagContainer).render(<Tags/>);
}