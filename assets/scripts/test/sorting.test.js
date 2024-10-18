import { sortPetz } from '../sorting';

test('Test sort by added', () => {
    let attrs = {
        configurable: true,
        value: {
            petz: {
                first: {
                    added: new Date('2023-03-12 03:00:00'),
                    pointsRollup: {},
                },
                third: {
                    added: new Date('2023-03-13 12:00:00'),
                    pointsRollup: {},
                },
                second: {
                    added: new Date('2023-03-12 05:00:00'),
                    pointsRollup: {},
                },
            },
            sorting: {
                attribute: 'added',
                attributeOrder: 'asc',
                points: null,
                pointsOrder: 'asc',
            }
        }
    };
    Object.defineProperty(global, 'window', attrs);
    expect(sortPetz(Object.keys(window.petz))).toEqual(['first','second','third']);

    attrs.value.sorting.attributeOrder = 'desc';
    Object.defineProperty(global, 'window', attrs);
    expect(sortPetz(Object.keys(window.petz))).toEqual(['third','second','first']);
});