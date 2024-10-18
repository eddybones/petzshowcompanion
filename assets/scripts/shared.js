export const alternateRows = (filteredPetz) => {
    let alternate = false;
    document.querySelectorAll('.pet.showing, .compactPet.showing').forEach(pet => {
        pet.style.display = 'flex';
        pet.classList.add('showing');
        pet.classList.remove('hiding');
        if(alternate) {
            pet.classList.add('alternate');
            alternate = false;
        } else {
            pet.classList.remove('alternate');
            alternate = true;
        }
    });
}