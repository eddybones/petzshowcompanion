import { binding, OneWayBind } from './binding';
import { doSorting } from './sorting';
import { alternateRows } from './shared';

const makeElement = (type, attrs, events) => {
    let element = document.createElement(type);
    Object.entries(attrs).forEach(attr => element.setAttribute(attr[0], attr[1]));
    if(events) {
        Object.entries(events).forEach(event => element.addEventListener(event[0], event[1]));
    }
    return element;
};

// Define each property as a function, or else use cloneNode for each Option,
// otherwise the Option node will get moved between selects.
const operatorGroups = {
    equality: () => [
        new Option('Equals', 'equals'),
        new Option('Not Equals', 'notequals'),
    ],
    quantity: () => [
        new Option('Greater Than', 'greaterthan'),
        new Option('Less Than', 'lessthan'),
    ],
    contains: () => [
        new Option('Contains', 'contains'),
        new Option('Does Not Contain', 'notcontains'),
    ],
};

const setFilterOperators = (value, /*string*/ id) => {
    const operatorSelect = document.getElementById(`filterOperator-${id}`);
    operatorSelect.innerHTML = '';

    switch(value) {
        case 'callname':
        case 'showname':
        case 'prefix':
        case 'hexerOrBreeder':
            operatorGroups.equality().forEach(option => operatorSelect.add(option));
            operatorGroups.contains().forEach(option => operatorSelect.add(option));
            break;
        case 'birthday':
        case 'added':
            operatorGroups.equality().forEach(option => operatorSelect.add(option));
            operatorGroups.quantity().forEach(option => operatorSelect.add(option));
            break;
        case 'sex':
        case 'retired':
        case 'species':
            operatorGroups.equality().forEach(option => operatorSelect.add(option));
            break;
        case 'showpoints':
            operatorGroups.equality().forEach(option => operatorSelect.add(option));
            operatorGroups.quantity().forEach(option => operatorSelect.add(option));
            break;
        case 'tags':
            operatorGroups.contains().forEach(option => operatorSelect.add(option));
            break;
    }

    operatorSelect.selectedIndex = 0;
    // Trigger immediately so binding updates
    operatorSelect.dispatchEvent(new Event('change'));
    operatorSelect.disabled = false;
};

const keydownEvent = (event) => {
    // Trigger binding first
    event.target.dispatchEvent(new Event('change'));
    if(event.key === 'Enter') {
        applyFilters();
    }
};

const valueFields = {
    multipart: (/*object*/ filter, /*string*/ id) => {
        const container = document.createElement('span');
        const select = makeElement(
            'select',
            { id: `filterShowType-${id}` },
            { change: (event) => OneWayBind.fromElement(binding(event.target, 'value', filter, 'value2'))}
        );
        // Name is intentionally used for both Option arguments
        showTypes.forEach(type => select.options.add(new Option(type.name, type.name)));
        container.appendChild(select);
        container.appendChild(valueFields.text(filter, id));
        return container;
    },
    text: (/*object*/ filter, /*string*/ id) => makeElement(
        'input',
        { name: `filterValue-${id}`, id: `filterValueInput-${id}` },
        {
            change: (event) => OneWayBind.fromElement(binding(event.target, 'value', filter, 'value')),
            keydown: keydownEvent,
        },
    ),
    date: (/*object*/ filter, /*string*/ id) => makeElement(
        'input',
        { name: `filterValue-${id}`, id: `filterValueInput-${id}`, placeholder: 'YYYY/MM/DD' },
        {
            change: (event) => OneWayBind.fromElement(binding(event.target, 'value', filter, 'value')),
            keydown: keydownEvent,
        },
    ),
    sex: (/*object*/ filter, /*string*/ id) => {
        let select = makeElement(
            'select',
            { name: `filterValue-${id}`, id: `filterValueInput-${id}` },
            { change: (event) => OneWayBind.fromElement(binding(event.target, 'value', filter, 'value')) },
        );
        sexTypes.forEach(sex => select.options.add(new Option(sex.name, sex.value)));
        return select;
    },
    boolean: (/*object*/ filter, /*string*/ id) => {
        let select = makeElement(
            'select',
            { name: `filterValue-${id}`, id: `filterValueInput-${id}` },

            { change: (event) => OneWayBind.fromElement(binding(event.target, 'value', filter, 'value')) },
        );
        select.options.add(new Option('Yes', '1'));
        select.options.add(new Option('No', '0'));
        return select;
    },
    tags: (/*object*/ filter, /*string*/ id) => {
        let tagsContainer = document.createElement('span');
        tagsContainer.className = 'tags';
        allTags.forEach(tag => {
            let container = makeElement('span', { class: 'tag' });
            let checkbox = makeElement(
                'input',
                {
                    type: 'checkbox',
                    name: `filterValue-${id}Tag_${tag.name.replace(/[^\w]/g, '')}`,
                    value: tag.value,
                },
                { change: (event) => OneWayBind.fromCheckboxGroup(binding(event.target, 'checked', filter, 'value')) },
            );
            container.innerHTML = tag.name;
            container.prepend(checkbox);
            tagsContainer.appendChild(container);
        });
        return tagsContainer;
    },
    species: (/*object*/ filter, /*string*/ id) => {
        let select = makeElement(
            'select',
            { name: `filterValue-${id}`, id: `filterValueInput-${id}` },
            { change: (event) => OneWayBind.fromElement(binding(event.target, 'value', filter, 'value')) },
        );
        species.forEach(type => select.options.add(new Option(type.name, type.value)));
        return select;
    },
};

const setValueElement = (value, /*string*/ id) => {
    const container = document.getElementById(`filterValue-${id}`);
    container.innerHTML = '';
    switch(value) {
        case 'callname':
        case 'showname':
        case 'prefix':
        case 'hexerOrBreeder':
            container.appendChild(valueFields.text(window.filters[id], id));
            window.filters[id].value = '';
            window.filters[id].value2 = '';
            break;
        case 'showpoints':
            container.appendChild(valueFields.multipart(window.filters[id], id));
            window.filters[id].value = '';
            window.filters[id].value2 = 'Pose';
            break;
        case 'birthday':
        case 'added':
            container.appendChild(valueFields.date(window.filters[id], id));
            window.filters[id].value = '';
            window.filters[id].value2 = '';
            break;
        case 'sex':
            container.appendChild(valueFields.sex(window.filters[id], id));
            window.filters[id].value = '';
            window.filters[id].value2 = '';
            break;
        case 'retired':
            container.appendChild(valueFields.boolean(window.filters[id], id));
            window.filters[id].value = '1';
            window.filters[id].value2 = '';
            break;
        case 'tags':
            container.appendChild(valueFields.tags(window.filters[id], id));
            window.filters[id].value = [];
            window.filters[id].value2 = '';
            break;
        case 'species':
            container.appendChild(valueFields.species(window.filters[id], id));
            window.filters[id].value = '1';
            window.filters[id].value2 = '';
            break;
    }
    container.style.display = 'block';
};

const changeFilterField = (field, /*string*/ id) => {
    setFilterOperators(field.value, id);
    setValueElement(field.value, id);
};

const matchFilterText = (/*string*/ petField, /*string*/ matchValue, operator) => {
    petField = petField.toLowerCase();
    matchValue = matchValue.toLowerCase();
    switch(operator) {
        case 'equals':
            return petField === matchValue;
            break;
        case 'notequals':
            return petField !== matchValue;
            break;
        case 'contains':
            return petField.includes(matchValue);
            break;
        case 'notcontains':
            return !petField.includes(matchValue);
            break;
    }
    return false;
};

export const matchFilterDate = (/*Date|null*/ petField, /*Date*/ matchValue, operator) => {
    switch(operator) {
        case 'equals':
            if(petField === null) {
                return false;
            }
            return petField.toGMTString() === matchValue.toGMTString();
            break;
        case 'notequals':
            if(petField === null) {
                return true;
            }
            return petField.toGMTString() !== matchValue.toGMTString();
            break;
        case 'greaterthan':
            if(petField === null) {
                return false;
            }
            return petField.getTime() > matchValue.getTime();
            break;
        case 'lessthan':
            if(petField === null) {
                return false;
            }
            return petField.getTime() < matchValue.getTime();
            break;
    }
    return false;
}

const matchFilterTags = (/*array*/ petField, /*array*/ matchValues, operator) => {
    let match = true;
    switch(operator) {
        case 'contains':
            // If no tags are selected and the pet has no tags, this is a matched pet.
            if(matchValues.length === 0) {
                return petField.length === 0;
            }
            matchValues.every(value => {
                if(!petField.includes(value)) {
                    match = false;
                    return false;
                }
                return true;
            });
            break;
        case 'notcontains':
            // If no tags are selected and the pet has tags, we want to match that.
            if(matchValues.length === 0) {
                return petField.length > 0;
            }
            matchValues.every(value => {
                if(petField.includes(value)) {
                    match = false;
                    return false;
                }
                return true;
            });
            break;
    }
    return match;
}

const matchFilterPoints = (/*Object|undefined*/ petField, /*string*/ matchValue, operator) => {
    let match = parseInt(matchValue);
    if(isNaN(match)) {
        match = 0;
    }
    let total = 0;
    if(petField !== undefined) {
        total = petField.total;
    }
    switch(operator) {
        case 'equals':
            return total === match;
            break;
        case 'notequals':
            return total !== match;
            break;
        case 'greaterthan':
            return total > match;
            break;
        case 'lessthan':
            return total < match;
            break;
    }
};

const filterFieldChangeEvent = (
    /*string*/ id,
    /*string*/ element) => document.getElementById(element).addEventListener(
    'change', event => {
        changeFilterField(event.target, id);
        OneWayBind.fromElement(binding(event.target, 'value', window.filters[id], 'field'));
    },
);

const filterOperatorChangeEvent = (/*int*/ id, /*string*/ element) => document.getElementById(element).addEventListener(
    'change',
    event => {
        OneWayBind.fromElement(binding(event.target, 'value', window.filters[id], 'operator'));
    },
);

const filterRemoveEvent = (/*string*/ id) => document.getElementById(`filterRemove-${id}`).addEventListener(
    'click',
    event => {
        const id = event.target.parentElement.id.split('-')[1];
        delete window.filters[id];
        document.getElementById(`filter-${id}`).remove();
        applyFilters();
        if(Object.keys(window.filters).length === 0) {
            addFilter();
        }
    },
);

const filterHandler = () =>
    document.getElementById('filterButton').addEventListener('click', event => {
        event.preventDefault();
        const filtersContainer = document.getElementById('filterContainer');
        if(getComputedStyle(filtersContainer).getPropertyValue('display') === 'none') {
            filtersContainer.style.display = 'block';
        } else {
            filtersContainer.style.display = 'none';
        }
    });

const applyFilters = () => {
    let allPetz = Object.keys(window.petz);
    let filterResult = [...allPetz];

    localStorage.setItem('filters', JSON.stringify(window.filters));

    const keys = Object.keys(window.filters);
    keys.forEach(key => {
        const filter = window.filters[key];
        const removeAt = (hash) => filterResult.splice(filterResult.indexOf(hash), 1);
        allPetz.forEach(hash => {
            switch(filter.field) {
                case 'callname':
                case 'showname':
                case 'prefix':
                case 'hexerOrBreeder':
                case 'sex':
                case 'retired':
                case 'species':
                    if(!matchFilterText(window.petz[hash][filter.field], filter.value, filter.operator)) {
                        removeAt(hash);
                    }
                    break;
                case 'birthday':
                case 'added':
                    if(!matchFilterDate(window.petz[hash][filter.field], new Date(filter.value), filter.operator)) {
                        removeAt(hash);
                    }
                    break;
                case 'showpoints':
                    // Show type is sorta hacked in but maybe should become an actual filter part somehow. It's just a special
                    // case for a singular filter right now, though, so I think it's fine to keep it this way unless (or until)
                    // we get another use-case to abstract it. The likelihood of that may be slim.
                    const showType = document.getElementById(`filterShowType-${key}`);
                    if(!matchFilterPoints(window.petz[hash]['pointsRollup'][showType.value], filter.value, filter.operator)) {
                        removeAt(hash);
                    }
                    break;
                case 'tags':
                    if(!matchFilterTags(window.petz[hash][filter.field], filter.value, filter.operator)) {
                        removeAt(hash);
                    }
                    break;
            }
        });
        allPetz = [...filterResult];
    });

    document.querySelectorAll('.pet, .compactPet').forEach(pet => {
        if(!allPetz.includes(pet.dataset.hash)) {
            pet.classList.remove('showing');
            pet.classList.add('hiding');
            pet.style.display = 'none';
        } else {
            pet.classList.remove('hiding');
            pet.classList.add('showing');
            pet.style.display = 'flex';
        }
    });

    doSorting();

    alternateRows(allPetz);

    if(Object.keys(window.petz).length !== allPetz.length) {
        document.getElementById('filtersApplied').style.display = 'inline-block';
    } else {
        document.getElementById('filtersApplied').style.display = 'none';
    }
};

const applyFiltersHandler = () =>
    document.getElementById('applyFilters').addEventListener('click', applyFilters);

const applyClearFiltersHandler = () =>
    document.querySelectorAll('.clearFilters').forEach(item => item.addEventListener('click', () => {
        const id = rando();
        window.filters = {};

        localStorage.removeItem('filters');

        const filtersContainer = document.getElementById('filters');
        filtersContainer.innerHTML = '';
        addFilter();

        document.querySelectorAll('.pet, .compactPet').forEach(pet => {
            pet.style.display = 'flex';
            pet.classList.remove('hidden');
            pet.classList.add('showing');
        });

        doSorting(window.petz);

        document.getElementById('filtersApplied').style.display = 'none';
    }));

const addEventHandlers = (/*string*/ id) => {
    filterFieldChangeEvent(id, `filterField-${id}`);
    filterOperatorChangeEvent(id, `filterOperator-${id}`);
    filterRemoveEvent(id);
};

const rando = () => Math.random().toString(36).slice(5);

const addFilterData = () => {
    const id = rando();
    window.filters[id] = {
        field: '',
        operator: '',
        value: '',
        value2: '',
    };
    return id;
};

const addFilterDom = (/*string*/ id) => {
    const template = `
        <span class="filter" id="filter-${id}">
            <select name="filterField-${id}" id="filterField-${id}">
                <option value="" selected disabled>Filter By...</option>
                <option value="showpoints">Show Points</option>
                <option value="species">Species</option>
                <option value="callname">Call Name</option>
                <option value="showname">Show Name</option>
                <option value="prefix">Prefix</option>
                <option value="hexerOrBreeder">Hexer/Breeder</option>
                <option value="sex">Sex</option>
                <option value="retired">Retired</option>
                <option value="birthday">Birthday</option>
                <option value="added">Date Added</option>
                <option value="tags">Tags</option>
            </select>
            <select name="filterOperator-${id}" id="filterOperator-${id}" disabled></select>
            <span class="filterValue" id="filterValue-${id}">
                <select name="filterValue-${id}" id="filterValueInput-${id}" disabled></select>
            </span>
            <span class="removeFilter" id="filterRemove-${id}">
                <span class="material-symbols-outlined">remove_circle</span>
            </span>
        </span>
    `;
    const range = document.createRange();
    range.selectNode(document.getElementById('filters'));
    const fragment = range.createContextualFragment(template);
    document.getElementById('filters').appendChild(fragment);
};

const addFilter = () => {
    const id = addFilterData();
    addFilterDom(id);
    addEventHandlers(id);
}

const addFilterHandler = () =>
    document.querySelector('#addFilter > span').addEventListener('click', () => {
        addFilter();
    });

const addDomHandler = () =>
    Object.keys(window.filters).forEach(key => {
        addFilterDom(key);
        addEventHandlers(key);

        // A clone is created because the event handlers can clear inputs that are later in the hierarchy
        // (i.e. changing the field can clear the operator and value fields)
        let filterClone = structuredClone(window.filters[key]);
        let event = new Event('change');

        if(filterClone.field !== '') {
            let fieldElement = document.getElementById(`filterField-${key}`);
            fieldElement.value = window.filters[key].field;
            fieldElement.dispatchEvent(event);
        }

        if(filterClone.operator !== '') {
            let operatorElement = document.getElementById(`filterOperator-${key}`);
            operatorElement.value = filterClone.operator;
            operatorElement.dispatchEvent(event);
        }

        // Special case - show points has a secondary option for show type
        if(filterClone.field === 'showpoints') {
            let typeElement = document.getElementById(`filterShowType-${key}`);
            typeElement.value = filterClone.value2;
        }

        if(filterClone.field === 'tags') {
            if(filterClone.value.length) {
                filterClone.value.forEach(x => {
                    document.querySelector(`.tag input[value='${x}']`).checked = true;
                });
            }
        } else {
            if(filterClone.value !== '') {
                let valueElement = document.getElementById(`filterValueInput-${key}`);
                valueElement.value = filterClone.value;
            }
        }

        window.filters[key] = filterClone;

        applyFilters();
    });

const filterConstruct = () => {
    filterHandler();
    applyFiltersHandler();
    applyClearFiltersHandler();
    addFilterHandler();
    addDomHandler();
}

filterConstruct();