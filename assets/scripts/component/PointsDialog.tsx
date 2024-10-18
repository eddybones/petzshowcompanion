import React = require('react');
import { render } from 'react-dom';
import {
    ChangeEventHandler,
    EventHandler,
    KeyboardEventHandler,
    ReactElement,
    useEffect,
    useRef,
    useState
} from 'react';
import Dialog from './Dialog';
import { SelectShowType } from './Common';
import styled from 'styled-components';
import { editPointsEvent } from '../points';

interface PointsDialogProps {
    useCompactView: boolean,
    hash: string,
    close: () => void,
}

interface Point {
    id: number,
    showType: number,
    points: number,
    addedOn: string,
    modified?: boolean,
    delete?: boolean,
}

interface RollupPoint {
    type: string,
    showType: number,
    title: string,
    total: number,
}

interface PointsFormProps {
    points: Point[],
    submit: () => void,
    typeChange: ChangeEventHandler,
    pointChange: ChangeEventHandler,
    pointKeydown: KeyboardEventHandler,
    checkDelete: EventHandler<any>,
}

const StyledPointsForm = styled.form`
  overflow-y: scroll;
  width: fit-content;
  max-height: 500px;
  
  header {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    text-align: center;
    font-weight: bold;
    margin-bottom: 8px;
  }
`;

const PointsForm = (props: PointsFormProps): ReactElement => {
    return (
        <StyledPointsForm>
            <header>
                <span className="title">Show Type</span>
                <span className="title">Points</span>
                <span className="title">Added</span>
                <span className="material-symbols-outlined">delete</span>
            </header>
            {props.points.map(point =>
                <PointRow point={point}
                          typeChange={props.typeChange}
                          pointChange={props.pointChange}
                          pointKeydown={props.pointKeydown}
                          checkDelete={props.checkDelete}
                          key={point.id}/>
            )}
            <button type="submit" onClick={props.submit}>Update</button>
        </StyledPointsForm>
    );
}

const StyledPointRow = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr;
  margin-bottom: 6px;
  text-align: center;

  input {
    width: 3em;
    margin: 0 auto;
  }

  .added {
    color: rgb(130, 130, 130);
  }
`;

interface PointRowProps {
    point: Point,
    typeChange: ChangeEventHandler,
    pointChange: ChangeEventHandler,
    pointKeydown: KeyboardEventHandler,
    checkDelete: EventHandler<any>,
}

const PointRow = (props: PointRowProps): ReactElement => {
    return (
        <StyledPointRow className="pointRow">
            <SelectShowType selectedType={props.point.showType} change={props.typeChange} id={props.point.id}/>
            <input type="text" name="points" defaultValue={props.point.points} data-id={props.point.id}
                   onChange={props.pointChange} onKeyDown={props.pointKeydown}/>
            <span className="added">{props.point.addedOn}</span>
            <span className="delete">
                <input type="checkbox" onChange={props.checkDelete} data-id={props.point.id}/>
            </span>
        </StyledPointRow>
    );
};

const recordModified = (original, modified): boolean => {
    return original.showType !== modified.showType || original.points !== modified.points;
}

const updateRollupInDOM = (useCompactView: boolean, rollup: RollupPoint[], hash: string): void => {
    // The code in this function is more or less duplicated from list.js. Rewriting that in React would probably be ideal.
    const element = <>
        {rollup.map((point) =>
            <span className="rank" key={'rank_' + point.showType}>
                <b>{point.type}:</b> ({point.total}) {point.title}
            </span>
        )}
        {rollup.length > 0 &&
            <span className="editPointsContainer">
                <a href="#"><span className="editPoints material-symbols-outlined"
                                  data-hash={hash}
                                  onClick={editPointsEvent}>edit_square</span></a>
            </span>
        }
    </>;

    if(useCompactView) {
        render(element, document.querySelector(`[data-hash="${hash}"] section .points`));
    } else {
        render(element, document.querySelector(`[data-hash="${hash}"] .points`));
    }

    // Update compact view's "summary" line as well.
    if(useCompactView) {
        const summary = rollup.map(point =>
            <span className="rank" key={'summary_rank_' + point.showType}>
               <b>{point.type}:</b> {point.total}
            </span>
        );
        render(summary, document.querySelector(`[data-hash="${hash}"] summary .points`));
    }
}

export default function PointsDialog(props: PointsDialogProps): ReactElement {
    const [showLoader, setShowLoader] = useState(true);
    const [error, setError] = useState(null);
    const [content, setContent] = useState(null as ReactElement | null);
    // We're not re-rendering based on points, we just want to maintain the current value.
    // https://stackoverflow.com/questions/54069253/the-usestate-set-method-is-not-reflecting-a-change-immediately#answer-58877875
    const pointsRef = useRef({});
    // "Static" original value from fetch. Used to compare against pointsRef to determine if a record is modified so we
    // can send only those records through to save to the database.
    const pointsOriginalRef = useRef({});

    const typeChange: ChangeEventHandler<HTMLInputElement> = (e) => {
        const id = e.target.dataset.id;
        pointsRef.current[id].showType = parseInt(e.target.value);
        pointsRef.current[id].modified = recordModified(pointsOriginalRef.current[id], pointsRef.current[id]);
    }

    const pointChange: ChangeEventHandler<HTMLInputElement> = (e) => {
        const id = e.target.dataset.id;
        pointsRef.current[id].points = parseInt(e.target.value);
        pointsRef.current[id].modified = recordModified(pointsOriginalRef.current[id], pointsRef.current[id]);
    }

    const pointKeydown: KeyboardEventHandler<HTMLInputElement> = (e) => {
        if (!e.key.match(/\d|Backspace|Delete|ArrowLeft|ArrowRight|Tab/)) {
            e.preventDefault();
        }
    }

    const checkDelete: EventHandler<any> = (e) => {
        const id = e.target.dataset.id;
        pointsRef.current[id].delete = e.target.checked;
    }

    const submitEvent = async () => {
        setContent(null);
        setShowLoader(true);
        const pointsToUpdate = [] as Point[];
        for (var id in pointsRef.current) {
            if (pointsRef.current[id].modified || pointsRef.current[id].delete) {
                pointsToUpdate.push(pointsRef.current[id]);
            }
        }

        if (pointsToUpdate.length) {
            const response = await fetch(`/petz/${props.hash}/points`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(pointsToUpdate),
            });

            if (!response.ok) {
                setContent(<p>There was an error saving points.</p>);
            } else {
                const data = await response.json();
                const parsed = JSON.parse(data) as RollupPoint[];
                updateRollupInDOM(props.useCompactView, parsed, props.hash);
                const newRollup = {};
                parsed.forEach(point => {
                    newRollup[point.type] = {
                        type: point.type,
                        total: point.total,
                        title: point.title,
                    };
                });
                window.petz[props.hash].pointsRollup = newRollup;
                setContent(<p>Points saved.</p>);
            }

            setShowLoader(false);
        } else {
            setContent(<p>Points saved.</p>);
            setShowLoader(false);
        }
    }

    useEffect(() => {
        fetch(`/petz/${props.hash}/points`)
            .then((response) => response.json())
            .then((data) => {
                const points = {};
                data.map(p => points[p.id] = p);
                pointsRef.current = points;
                pointsOriginalRef.current = structuredClone(points);
                setContent(<PointsForm points={data}
                                       typeChange={typeChange}
                                       pointChange={pointChange}
                                       pointKeydown={pointKeydown}
                                       checkDelete={checkDelete}
                                       submit={submitEvent}/>
                );
            })
            .catch(() => setError(<p>Error getting points. Try closing and reopening.</p>))
            .finally(() => setShowLoader(false));
    }, []);

    return <Dialog title="Edit Points"
                   showLoader={showLoader}
                   error={error}
                   content={content}
                   close={props.close}/>
}