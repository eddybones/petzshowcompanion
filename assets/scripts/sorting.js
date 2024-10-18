import { alternateRows } from './shared';

const defaultSorting = {
    attribute: 'callname',
    attributeOrder: 'asc',
    points: null,
    pointsOrder: 'asc',
};

export const sortPetz = (/* array */ petHashes) => {
    let sortingFunction;
    const sortDelegate = (a, b) => (a < b) ? -1 : (a > b) ? 1 : 0;
    const localeSortDelegate = (a, b) => a.toUpperCase().localeCompare(b.toUpperCase());
    const pointsSortDelegate = (a, b) => {
        const sortPoints = window.sorting.points !== null;
        if(!sortPoints) {
            return null;
        }
        const pointsA = (a.pointsRollup[window.sorting.points]) ? a.pointsRollup[window.sorting.points].total : 0;
        const pointsB = (b.pointsRollup[window.sorting.points]) ? b.pointsRollup[window.sorting.points].total : 0;
        let pointsSortValue = sortDelegate(pointsA, pointsB);
        if(window.sorting.pointsOrder === 'desc') {
            pointsSortValue *= -1;
        }
        return pointsSortValue;
    }

    let placeholder = new Date(5000000000000);
    if(window.sorting.attributeOrder === 'desc') {
        placeholder = new Date(10000000000);
    }

    const speciesText = (/*string*/ species) => {
        switch(species) {
            case '1': return 'Dog';
            case '2': return 'Cat';
            case '3': return 'Wildz';
            case '4': return 'Other';
        }
    }

    switch(window.sorting.attribute) {
        case 'species':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                const attributeSortValue = (window.sorting.attributeOrder === 'desc') ? localeSortDelegate(speciesText(b.species), speciesText(a.species)) : localeSortDelegate(speciesText(a.species), speciesText(b.species));
                return pointsSortDelegate(a, b) ?? attributeSortValue;
            }
            break;
        case 'callname':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                const attributeSortValue = (window.sorting.attributeOrder === 'desc') ? localeSortDelegate(b.callname, a.callname) : localeSortDelegate(a.callname, b.callname);
                return pointsSortDelegate(a, b) ?? attributeSortValue;
            }
            break;
        case 'showname':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                const attributeSortValue = (window.sorting.attributeOrder === 'desc') ? localeSortDelegate(b.showname, a.showname) : localeSortDelegate(a.showname, b.showname);
                return pointsSortDelegate(a, b) ?? attributeSortValue;
            }
            break;
        case 'prefix':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                const attributeSortValue = (window.sorting.attributeOrder === 'desc') ? localeSortDelegate(b.prefix, a.prefix) : localeSortDelegate(a.prefix, b.prefix);
                return pointsSortDelegate(a, b) ?? attributeSortValue;
            }
            break;
        case 'hexerOrBreeder':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                const attributeSortValue = (window.sorting.attributeOrder === 'desc') ? localeSortDelegate(b.hexerOrBreeder, a.hexerOrBreeder) : localeSortDelegate(a.hexerOrBreeder, b.hexerOrBreeder);
                return pointsSortDelegate(a, b) ?? attributeSortValue;
            }
            break;
        case 'sex':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                const attributeSortValue = (window.sorting.attributeOrder === 'desc') ? localeSortDelegate(b.sex, a.sex) : localeSortDelegate(a.sex, b.sex);
                return pointsSortDelegate(a, b) ?? attributeSortValue;
            }
            break;
        case 'retired':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                return pointsSortDelegate(a, b) ?? sortDelegate(a.retired, b.retired);
            }
            break;
        case 'birthday':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                const attributeSortValue = (window.sorting.attributeOrder === 'desc') ? sortDelegate(b.birthday ?? placeholder, a.birthday ?? placeholder) : sortDelegate(a.birthday ?? placeholder, b.birthday ?? placeholder);
                return pointsSortDelegate(a, b) ?? attributeSortValue;
            }
            break;
        case 'added':
            sortingFunction = (a, b) => {
                a = window.petz[a];
                b = window.petz[b];
                const attributeSortValue = (window.sorting.attributeOrder === 'desc') ? sortDelegate(b.added, a.added) : sortDelegate(a.added, b.added);
                return pointsSortDelegate(a, b) ?? attributeSortValue;
            }
            break;
    }

    petHashes.sort(sortingFunction);
    // This one is for points which sorta overrides everything else if selected.
    if(window.sorting.order === 'desc') {
        petHashes.reverse();
    }

    return petHashes;
};

export const doSorting = () => {
    let filteredPetz = Array.from(document.querySelectorAll('.pet.showing, .compactPet.showing'));

    let sorted = sortPetz(filteredPetz.map(pet => pet.dataset.hash));
    sorted.forEach(hash => {
        document.getElementById('petz').appendChild(document.querySelector(`[data-hash="${hash}"]`));
    });
    alternateRows(filteredPetz);
};

const applySortingHandler = () =>
    document.getElementById('applySorting').addEventListener('click', event => {
        const showPointsElement = document.getElementById('sortByShowPoints');
        const attributeElement = document.getElementById('sortByAttribute');

        const pointsValue = (showPointsElement.value !== '') ? showPointsElement.value : null;
        const pointsOrder = showPointsElement.selectedOptions[0].dataset.order ?? 'asc';
        const attributeValue = attributeElement.value;
        const attributeOrder = attributeElement.selectedOptions[0].dataset.order;

        window.sorting.points = pointsValue;
        window.sorting.pointsOrder = pointsOrder;
        window.sorting.attribute = attributeValue;
        window.sorting.attributeOrder = attributeOrder;

        localStorage.setItem('sorting', JSON.stringify(window.sorting));

        doSorting();

        if(JSON.stringify(window.sorting).localeCompare(JSON.stringify(defaultSorting)) !== 0) {
            document.getElementById('sortingApplied').style.display = 'inline-block';
        }
    });

const sortButtonHandler = () =>
    document.getElementById('sortButton').addEventListener('click', event => {
        event.preventDefault();
        const sort = document.getElementById('sortContainer');
        if(getComputedStyle(sort).getPropertyValue('display') === 'none') {
            sort.style.display = 'block';
        } else {
            sort.style.display = 'none';
        }
    });

const resetSortHandler = () =>
    document.querySelectorAll('.resetSorting').forEach(element => element.addEventListener('click', event => {
        localStorage.removeItem('sorting');
        window.sorting = structuredClone(defaultSorting);
        document.getElementById('sortByShowPoints').selectedIndex = 0;
        document.getElementById('sortByAttribute').selectedIndex = 0;

        document.getElementById('applySorting').click();

        document.getElementById('sortingApplied').style.display = 'none';
    }));

const sortInitHandler = () => {
    if(window.sorting !== defaultSorting) {
        const showPointsElement = document.getElementById('sortByShowPoints');
        const points = window.sorting.points ?? '';
        if(points !== "") {
            showPointsElement.querySelector(`[value="${points}"][data-order="${window.sorting.pointsOrder}"]`).selected = true;
        } else {
            showPointsElement.querySelector(`[value=""]`).selected = true;
        }

        const attributeElement = document.getElementById('sortByAttribute');
        attributeElement.querySelector(`[value="${window.sorting.attribute}"][data-order="${window.sorting.attributeOrder}"]`).selected = true;

        document.getElementById('applySorting').click();
    }
}

const sortConstruct = () => {
    applySortingHandler();
    sortButtonHandler();
    resetSortHandler();
    sortInitHandler();
}

sortConstruct();