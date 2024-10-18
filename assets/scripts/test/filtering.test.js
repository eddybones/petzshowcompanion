import { matchFilterDate } from '../filtering';

const date = new Date('2023-03-12 03:16:00');
const laterDate = new Date('2023-03-12 03:17:00');

test('Test matchFilterDate equals operator', () => {
    const operator = 'equals';
    expect(matchFilterDate(null, date, operator)).toBe(false);
    expect(matchFilterDate(date, date, operator)).toBe(true);
    expect(matchFilterDate(date, laterDate, operator)).toBe(false);
});

test('Test matchFilterDate notequals operator', () => {
    const operator = 'notequals';
    expect(matchFilterDate(null, Date(), operator)).toBe(true);
    expect(matchFilterDate(date, date, operator)).toBe(false);
    expect(matchFilterDate(date, laterDate, operator)).toBe(true);
});

test('Test matchFilterDate greaterthan operator', () => {
    const operator = 'greaterthan';
    expect(matchFilterDate(null, Date(), operator)).toBe(false);
    expect(matchFilterDate(date, date, operator)).toBe(false);
    expect(matchFilterDate(date, laterDate, operator)).toBe(false);
    expect(matchFilterDate(laterDate, date, operator)).toBe(true);
});

test('Test matchFilterDate lessthan operator', () => {
    const operator = 'lessthan';
    expect(matchFilterDate(null, Date(), operator)).toBe(false);
    expect(matchFilterDate(date, date, operator)).toBe(false);
    expect(matchFilterDate(date, laterDate, operator)).toBe(true);
    expect(matchFilterDate(laterDate, date, operator)).toBe(false);
});
