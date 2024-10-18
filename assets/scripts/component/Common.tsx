import React = require('react');
import { ChangeEventHandler, ReactElement } from 'react';

interface SelectShowTypeProps {
    id?: number,
    selectedType: number,
    change?: ChangeEventHandler,
}

export const SelectShowType = (props: SelectShowTypeProps): ReactElement => {
    return (
        <select name="showtype" defaultValue={props.selectedType} onChange={props.change} data-id={props.id}>
            <option value="1">Pose</option>
            <option value="2">Trick</option>
            <option value="3">Agility</option>
            <option value="4">Frisbee</option>
            <option value="5">Mousing</option>
        </select>
    );
}