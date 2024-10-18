interface Rollup {
    showType: string,
    total: number,
}

interface Tag {
    hash: string,
    name: string,
}

export interface Pic {
    id: number,
    file: string,
    order: number,
}

export interface Pet {
    id: number,
    hash: string,
    type: string,
    retired: boolean,
    addedOn: string,
    callName?: string,
    showName?: string,
    prefix?: string,
    hexerOrBreeder?: string,
    sex?: string,
    birthday?: string,
    rollup?: Rollup[],
    tags?: Tag[],
    pics?: Pic[],
    notes?: string,
}