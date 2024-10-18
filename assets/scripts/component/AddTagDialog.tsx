import React = require('react');
import Dialog from './Dialog';
import { Dispatch, ReactElement, SetStateAction, useState } from 'react';
import styled from 'styled-components';
import { Tag } from './Tags';

interface AddTagDialogProps {
    setTags: Dispatch<SetStateAction<Tag[]>>,
    close: () => void,
}

interface AddTag {
    name: string,
    index: number,
}

const AddTagForm = styled.form`
  input {
    width: 90%;
  }

  #addTagContainer {
    display: flex;
    justify-content: start;
    align-items: center;
    width: 100%;
    margin-bottom: 10px;
  }

  .material-symbols-outlined {
    font-size: 20px;
  }
`;

export default function AddTagDialog(props: AddTagDialogProps): ReactElement {
    const [showLoader, setShowLoader] = useState(false);
    const [tags, setTags] = useState([{name: "", index: 0}] as AddTag[]);
    const [error, setError] = useState(false);
    const [added, setAdded] = useState(false);

    const change = (e) => {
        tags[e.target.dataset.index].name = e.target.value;
        setTags([...tags]);
    };

    const add = (e) => {
        e.preventDefault();
        setTags([...tags, {name: "", index: tags.length}]);
    };

    const save = async(e) => {
        e.preventDefault();

        setShowLoader(true);
        const response = await fetch('/tags/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(tags),
        });

        if(!response.ok) {
            setError(true);
        } else {
            await response.json().then((data) => props.setTags(data));
            setAdded(true);
        }
        setShowLoader(false);
    };

    const content =
        <AddTagForm>
            {added
                ? <p>Tag(s) added.</p>
                : error
                    ? <p>Error saving tags. Close and try again.</p>
                    : <>
                        <div id="tags">
                            {tags.map(tag =>
                                <div key={"tag" + tag.index}>
                                    <label
                                        htmlFor={"tag" + tag.index}>Tag{tag.index !== 0 ? ` ${tag.index + 1}` : ''}</label>
                                    <input type="text" data-index={tag.index} name={"tag" + tag.index}
                                           defaultValue={tag.name} onChange={change}/>
                                </div>)}
                        </div>

                        <div id="addTagContainer">
                            <span className="material-symbols-outlined">add</span>
                            <a href="#" onClick={add}>Add another</a>
                        </div>

                        <button onClick={save}>Save</button>
                    </>
            }
        </AddTagForm>;

    return (
        <Dialog title="Add Tag" showLoader={showLoader} content={content} close={props.close} width={400} height={350}/>
    );
}