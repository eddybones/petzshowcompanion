import React = require('react');
import styled from 'styled-components';
import Loader from './Loader';
import {Pic} from '../common/Interfaces';
import {ReactElement, useEffect, useState, useRef, MutableRefObject} from 'react';
import {DndContext} from '@dnd-kit/core';
import {SortableContext, useSortable, rectSwappingStrategy, arrayMove} from '@dnd-kit/sortable';
import {CSS} from '@dnd-kit/utilities';
import ConfirmDialog from "./ConfirmDialog";
import OkDialog from "./OkDialog";

const StyledPicContainer = styled.div<{ $sortEnabled: boolean }>`
  float: left;
  margin: 0 20px 20px 0;

  .pic {
    display: flex;
    justify-content: center;
    width: 154px;
    height: 151px;
    min-width: 154px;
    min-height: 151px;
    background: #ffffff;
    box-shadow: 1px 1px 3px rgba(0, 0, 0, 40%);
    border-radius: 4px;
    padding: 2px;
    cursor: ${props => props.$sortEnabled ? 'move' : 'initial'};

    img {
      align-self: center; /* This will prevent the image from scaling up */
      max-width: 100%;
      max-height: 100%;
    }
  }

  .tools {
    background: #ccc;
    height: 16px;
    padding: 4px;
    border-radius: 4px 4px 0 0;

    .delete {
      float: right;
      background: #dc684d;
      color: #000000;
      width: fit-content;
      padding: 4px;
      margin: -4px -4px 0 0;
      border-radius: 4px 4px 0 0;
      border: 1px solid #dc684d;
      height: 24px;
      cursor: pointer;
    }
  }
`;

interface PicContainerProps {
    pic: Pic,
    ids: number[],
    setIds: React.Dispatch<React.SetStateAction<[]>>,
    idToDelete: MutableRefObject<number>,
    setShowDeleteConfirmation: React.Dispatch<React.SetStateAction<boolean>>,
    sortEnabled: boolean,
}

const PicContainer = (props: PicContainerProps): ReactElement => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition
    } = useSortable({id: props.pic.id});
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <StyledPicContainer ref={setNodeRef} style={style} {...attributes} {...listeners}>
            <div className="tools">
                #{props.pic.order}
                {!props.sortEnabled &&
                    <button className="delete" type="button" onClick={() => {
                        props.idToDelete.current = props.pic.id;
                        props.setShowDeleteConfirmation(true);
                    }}>X
                    </button>}
            </div>
            <div className="pic">
                <img src={`/pics/${props.pic.file}`}/>
            </div>
        </StyledPicContainer>
    );
};

const StyledPicForm = styled.div`
  position: relative;

  h2 {
    margin-top: 0;
  }

  #pics {
    margin-bottom: 10px;

    & > .clear {
      clear: both;
      height: 0;
    }
  }

  #picCount {
    margin-bottom: 20px;

    & > button.resort {
      display: flex;
      margin-top: 10px;

      & > span {
        font-size: 18px;
        margin-right: 4px;
      }
    }
  }

  .picButton {
    border: 1px solid #000;
    border-radius: 4px;
    margin-right: 10px;
    cursor: pointer;
  }

  #sortConfirm {
    background: #92d18a;
  }

  #sortCancel {
    background: #dc684d;
  }

  #addPics {
    clear: both;

    & > #uploadActions {
      display: flex;

      input {
        border: 1px solid #000;
        border-radius: 4px;
        margin: 0 10px 0 0;
        padding: 4px;
      }

      #clearPicUpload {
        background: #dc684d;
      }

      #uploadPics {
        background: #92d18a;
      }
    }
  }
`;

const deletePic = (
    picId: number,
    ids: number[],
    setIds: React.Dispatch<React.SetStateAction<number[]>>,
    pics: Pic[],
    setPics: React.Dispatch<React.SetStateAction<Pic[]>>,
    setShowOkLoader: React.Dispatch<React.SetStateAction<boolean>>,
    setOkMessage: React.Dispatch<React.SetStateAction<string>>
) => {
    fetch(`/pics/${picId}/delete`, {
        method: 'DELETE'
    })
        .then(response => {
            if (!response.ok) {
                throw response;
            }
        })
        .then(() => {
            ids.splice(ids.indexOf(picId), 1);
            setIds(ids);
            let newPics = pics.filter((pic) => pic.id != picId);
            newPics.map((pic) => pic.order = ids.indexOf(pic.id) + 1);
            setPics(newPics);
            setOkMessage('Pic deleted.');
        })
        .catch(() => {
            setOkMessage('Error deleting pic.');
        })
        .finally(() => setShowOkLoader(false));
};

const saveSortOrder = (
    hash: string,
    originalPicSort: number[],
    picSort: number[],
    setIds: React.Dispatch<React.SetStateAction<number[]>>,
    pics: Pic[],
    setPics: React.Dispatch<React.SetStateAction<Pic[]>>,
    setOkMessage: React.Dispatch<React.SetStateAction<string>>,
    setShowOkDialog: React.Dispatch<React.SetStateAction<boolean>>,
    setShowOkLoader: React.Dispatch<React.SetStateAction<boolean>>
) => {
    if (originalPicSort.length == picSort.length &&
        originalPicSort.every((value, index) => picSort.indexOf(value) == index)
    ) {
        return;
    }

    setShowOkLoader(true);
    setShowOkDialog(true);

    fetch(`/pics/resort`, {
        method: 'POST',
        body: JSON.stringify(picSort),
    })
        .then(response => {
            if (!response.ok) {
                throw response;
            }
            setOkMessage('Pic sort order saved.');
        })
        .catch(() => {
            // Reset data
            setIds(originalPicSort);
            let newPics = [...pics];
            newPics.map((pic) => pic.order = originalPicSort.indexOf(pic.id) + 1);
            newPics.sort((a, b) => a.id >= b.id ? 1 : 0);
            setPics(newPics);
            setOkMessage('Error saving pic sort order.');
        })
        .finally(() => setShowOkLoader(false));
};

const uploadPics = (
    hash: string,
    picSelect: React.MutableRefObject<any>,
    setShowOkLoader: React.Dispatch<React.SetStateAction<boolean>>,
    setOkMessage: React.Dispatch<React.SetStateAction<string>>,
    setShowLoader: React.Dispatch<React.SetStateAction<boolean>>,
    setPicsSelected: React.Dispatch<React.SetStateAction<boolean>>,
    setReload: React.Dispatch<React.SetStateAction<boolean>>,
    reload: boolean,
) => {
    const pics = picSelect.current.files;
    const formData = new FormData();
    for (let i = 0; i < pics.length; ++i) {
        formData.append(`file${i}`, pics[i], pics[i].name);
    }

    fetch(`/pet/${hash}/pics/add`, {
        method: 'POST',
        body: formData,
    })
        .then(response => {
            if (!response.ok) {
                throw response;
            }
            picSelect.current.value = null;
            setPicsSelected(false);
            setOkMessage('Pics saved.');
            setShowLoader(true);
            setReload(!reload);
        })
        .catch(() => {
            setOkMessage('Error saving pics.');
        })
        .finally(() => setShowOkLoader(false));
};

export const PicForm = (props: { hash: string | null }): ReactElement => {
    const [showLoader, setShowLoader] = useState(true);
    const [pics, setPics] = useState([] as Pic[]);
    const [message, setMessage] = useState(null);
    const [ids, setIds] = useState([] as number[]);
    const [showDeleteConfirmation, setShowDeleteConfirmation] = useState(false);
    const [showOkLoader, setShowOkLoader] = useState(true);
    const [showOkDialog, setShowOkDialog] = useState(false);
    const [okMessage, setOkMessage] = useState('');
    const [sortEnabled, setSortEnabled] = useState(false);
    const [picsSelected, setPicsSelected] = useState(false);
    const [reload, setReload] = useState(true);
    const picSelect = useRef(null);
    const idToDelete = useRef(0);
    const originalPicSort = useRef('');

    useEffect(() => {
        if (props.hash) {
            fetch(`/petz/${props.hash}/pics`)
                .then(response => response.json())
                .then((data) => {
                    let dataset = JSON.parse(data);
                    setPics(dataset);
                    let idList = dataset.map(x => x.id);
                    setIds(idList);
                    originalPicSort.current = idList.toString();
                })
                .catch(() => setMessage('Error getting pics.'))
                .finally(() => setShowLoader(false));
        } else {
            setShowLoader(false);
        }
    }, [reload]);

    return (
        <DndContext onDragEnd={handleDragEnd}>
            <SortableContext items={ids} strategy={rectSwappingStrategy} disabled={!sortEnabled}>
                <StyledPicForm>
                    <input type="hidden" name="originalPicSort" value={originalPicSort.current}/>
                    <input type="hidden" name="picSort" value={ids.toString()}/>
                    <h2>Pics</h2>
                    <div id="pics">
                        {!showLoader && message && <p>{message}</p>}
                        {showLoader && <Loader small={true} center={false}/>}
                        {showOkDialog &&
                            <OkDialog isOpen={true}
                                      showLoader={showOkLoader}
                                      message={okMessage}
                                      onOk={() => setShowOkDialog(false)}/>}
                        {!showLoader && showDeleteConfirmation &&
                            <ConfirmDialog isOpen={true}
                                           showLoader={false}
                                           message={'Do you really want to delete this pic?'}
                                           onYes={() => {
                                               setShowDeleteConfirmation(false);
                                               setShowOkDialog(true);
                                               setShowOkLoader(true);
                                               deletePic(idToDelete.current, ids, setIds, pics, setPics, setShowOkLoader, setOkMessage);
                                               idToDelete.current = 0;
                                           }}
                                           onNo={() => {
                                               idToDelete.current = 0;
                                               setShowDeleteConfirmation(false);
                                           }}/>}
                        {!showLoader &&
                            <StyledPicContainer>
                                <div id="picCount">
                                    {!sortEnabled && <b>{pics.length} of 6</b>}
                                    {pics.length >= 2 && !sortEnabled &&
                                        <button type="button" className="resort" onClick={() => setSortEnabled(true)}>
                                            <span className="material-symbols-outlined">shuffle</span> Reorder
                                        </button>}
                                    {sortEnabled && <>
                                        <button type="button" className="picButton" id="sortConfirm"
                                                onClick={() => {
                                                    saveSortOrder(
                                                        props.hash,
                                                        originalPicSort.current.split(',').map(num => parseInt(num)),
                                                        ids,
                                                        setIds,
                                                        pics,
                                                        setPics,
                                                        setOkMessage,
                                                        setShowOkDialog,
                                                        setShowOkLoader
                                                    );
                                                    setSortEnabled(false);
                                                }}>
                                            <span className="material-symbols-outlined">check_box</span>
                                        </button>
                                        <button type="button" className="sortAction" id="sortCancel"
                                                onClick={() => setSortEnabled(false)}>
                                            <span className="material-symbols-outlined">cancel</span>
                                        </button>
                                    </>}
                                </div>
                                {pics.map(x => <PicContainer key={x.id}
                                                             pic={x}
                                                             ids={ids}
                                                             setIds={setIds}
                                                             idToDelete={idToDelete}
                                                             setShowDeleteConfirmation={setShowDeleteConfirmation}
                                                             sortEnabled={sortEnabled}/>)}
                            </StyledPicContainer>
                        }
                        <div className="clear"></div>
                    </div>
                    {!showLoader && !sortEnabled &&
                        <div id="addPics">
                            <p>(Select one or more)</p>
                            <div id="uploadActions">
                                <input type="file" ref={picSelect} name="pics[]" disabled={pics.length >= 6}
                                       multiple={true} onInput={() => setPicsSelected(true)}/>
                                {picsSelected && props.hash &&
                                    <>
                                        <button id="uploadPics" className="picButton" type="button" onClick={() => {
                                            setShowOkLoader(true);
                                            setShowOkDialog(true);
                                            uploadPics(props.hash, picSelect, setShowOkLoader, setOkMessage, setShowLoader, setPicsSelected, setReload, reload);
                                        }}><span className="material-symbols-outlined">upload</span></button>
                                        <button id="clearPicUpload" className="picButton" type="button" onClick={() => {
                                            picSelect.current.value = null;
                                            setPicsSelected(false);
                                        }}><span className="material-symbols-outlined">cancel</span></button>
                                    </>
                                }
                            </div>
                        </div>
                    }
                </StyledPicForm>
            </SortableContext>
        </DndContext>
    );

    function handleDragEnd(event) {
        const {active, over} = event;

        if (active.id !== over.id) {
            setIds((ids) => {
                const oldIndex = ids.indexOf(active.id);
                const newIndex = ids.indexOf(over.id);
                return arrayMove(ids, oldIndex, newIndex);
            });

            setPics((pics) => {
                const oldIndex = pics.map(x => x.id).indexOf(active.id);
                const newIndex = pics.map(x => x.id).indexOf(over.id);
                pics[oldIndex] = pics.splice(newIndex, 1, pics[oldIndex])[0];
                pics[oldIndex].order = oldIndex + 1;
                pics[newIndex].order = newIndex + 1;
                return pics;
            });
        }
    }
}