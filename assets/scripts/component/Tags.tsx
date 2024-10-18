import React = require('react');
import styled from 'styled-components';
import { EventHandler, ReactElement, useEffect, useRef, useState } from 'react';
import AddTagDialog from './AddTagDialog';
import Loader from './Loader';
import ConfirmDialog from './ConfirmDialog';

export interface Tag {
    name: string,
    hash: string,
    private: boolean,
    editing?: boolean,
}

const StyledTags = styled.div`
  #tagActions {
    display: flex;
    justify-content: end;
    align-items: center;
    width: 400px;

    .material-symbols-outlined {
      font-size: 20px;
    }
  }

  #tagList {
    width: 400px;
    border: 1px solid #000000; /* $primary-border-color; */
    border-radius: 6px;
    list-style: none;
    padding: 0;

    .actions {
      cursor: pointer;
    }

    li {
      position: relative;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px;
      min-height: 24px;

      .name {
        margin-right: auto;
      }

      .edit, .save, .delete, .cancel {
        font-size: 20px;
      }

      .save {
        color: #009143;
        cursor: pointer;
        font-size: 24px;
      }

      .cancel {
        cursor: pointer;
        font-size: 24px;
      }

      .editTag {
        display: flex;

        input {
          width: 250px;
          margin-right: 2px;
        }
      }
    }

    li:nth-child(odd) {
      background-color: #eeeeee;
    }

    li:first-child {
      border-radius: 6px 6px 0 0;
    }

    li:last-child {
      border-radius: 0 0 6px 6px;
    }
  }
`;

export default function Tags(): ReactElement {
    const [showLoader, setShowLoader] = useState(true);
    const [message, setMessage] = useState(null);
    const [tags, setTags] = useState([] as Tag[]);
    const [saving, setSaving] = useState(false);
    const [showAddDialog, setShowAddDialog] = useState(false);
    const [showDeleteConfirmation, setShowDeleteConfirmation] = useState(false);
    const [showDeleteConfirmationLoader, setShowDeleteConfirmationLoader] = useState(false);
    const hashToDeleteRef = useRef(null);
    const nameUpdateRef = useRef(null);

    const addTagDialog: EventHandler<any> = (e): void => {
        e.preventDefault();
        setShowAddDialog(true);
    };

    const deleteTagDialog: EventHandler<any> = (e): void => {
        e.preventDefault();
        setMessage(null);
        hashToDeleteRef.current = e.target.dataset.hash;
        setShowDeleteConfirmation(true);
    };

    const deleteTag = async() => {
        setMessage(null);
        setShowDeleteConfirmationLoader(true);

        const response = await fetch(`/tags/${hashToDeleteRef.current}`, {
            method: 'DELETE',
        });

        if(response.ok) {
            setTags([...tags.filter(t => t.hash !== hashToDeleteRef.current)]);
            hashToDeleteRef.current = null;
            setMessage('Tag deleted.');
        } else {
            setMessage('Error deleting tag.');
        }

        setShowDeleteConfirmationLoader(false);
        setShowDeleteConfirmation(false);
    };

    const editTag: EventHandler<any> = (e) => {
        setMessage(null);
        const hash = e.target.dataset.hash;
        tags.map(t => {
            if(t.hash === hash) {
                t.editing = true;
            }
        })
        setTags([...tags]);
    };

    const cancelEdit: EventHandler<any> = (e) => {
        setMessage(null);
        const hash = e.target.dataset.hash;
        tags.map(t => {
            if(t.hash === hash) {
                t.editing = false;
            }
        })
        setTags([...tags]);
    }

    const saveTag: EventHandler<any> = async(e) => {
        setMessage(null);
        setSaving(true);
        const hash = e.target.dataset.hash;
        // TODO: Get this from the form
        const isPrivate = 1;
        const newName = nameUpdateRef.current.value;

        const response = await fetch(`/tags/${hash}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({name: newName, private: isPrivate}),
        });

        tags.map(t => {
            if(t.hash === hash) {
                t.editing = false;
                t.name = response.ok ? newName : t.name;
            }
        });
        setTags([...tags]);

        if(!response.ok) {
            setMessage('Error saving tag.');
        } else {
            setMessage('Tag saved.');
        }

        setSaving(false);
    }

    useEffect(() => {
        fetch('/tags/list')
            .then(response => response.json())
            .then((data) => {
                setTags(data);
                setShowLoader(false);
            })
            .catch(() => setMessage('Error getting tags.'))
            .finally(() => setShowLoader(false));
    }, []);

    return (
        <StyledTags>
            <h1>Tags</h1>
            {message && <div className="message">{message}</div>}
            {showLoader
                ? <Loader/>
                : tags.length == 0
                    ? <p>You haven't added any tags yet. <a href="#" onClick={addTagDialog}>Add some</a>.</p>
                    : <>
                        <div id="tagActions">
                            <span className="material-symbols-outlined">add</span>
                            <a href="#" onClick={addTagDialog}>Add</a>
                        </div>
                        <ul id="tagList">
                            {tags.map(tag =>
                                <li key={'tag' + tag.hash}>
                                    {tag.editing
                                        ? <span className="editTag">
                                            {saving
                                                ? <Loader smallest={true}/>
                                                : <>
                                                    <input defaultValue={tag.name} data-hash={tag.hash}
                                                           ref={nameUpdateRef}/>
                                                    <span className="material-symbols-outlined save"
                                                          data-hash={tag.hash}
                                                          onClick={saveTag}>save</span>
                                                    <span className="material-symbols-outlined cancel"
                                                          data-hash={tag.hash}
                                                          onClick={cancelEdit}>cancel</span>
                                                </>
                                            }
                                        </span>
                                        : <span className="name">{tag.name}</span>
                                    }
                                    {!tag.editing &&
                                        <span className="actions">
                                            <span className="material-symbols-outlined edit" data-hash={tag.hash}
                                                  onClick={editTag}>edit</span>
                                            <span className="material-symbols-outlined delete" data-hash={tag.hash}
                                                  onClick={deleteTagDialog}>delete</span>
                                        </span>
                                    }
                                </li>)}
                        </ul>
                    </>
            }
            {showAddDialog && <AddTagDialog setTags={setTags} close={() => setShowAddDialog(false)}/>}
            <ConfirmDialog message="Are you sure you want to delete this tag?"
                           showLoader={showDeleteConfirmationLoader}
                           isOpen={showDeleteConfirmation}
                           onYes={deleteTag}
                           onNo={() => {
                               hashToDeleteRef.current = null;
                               setShowDeleteConfirmation(false);
                           }}/>
        </StyledTags>
    )
}