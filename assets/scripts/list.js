/**
 * Show Type
 */
import { editPointsEvent } from './points';

document.querySelectorAll('select[name=showtype]').forEach(select => {
    select.addEventListener('change', function() {
        const rankSelect = this.parentNode.querySelector('select.rank');
        rankSelect.options.length = 0;

        let optionGroup = poseRankPoints;
        if(this.selectedOptions[0].text !== 'Pose') {
            optionGroup = rankPoints;
        }

        optionGroup.map((rank) => {
            rankSelect.appendChild(new Option(`${rank.name} (${rank.value})`, rank.value));
        });
    });
});

/**
 * Points
 */
document.querySelectorAll('.ok').forEach(element => {
    // This will allow the OK animation to "reset" when it's done
    element.addEventListener('animationend', event => {
        element.style.display = 'none';
    });
});

document.querySelectorAll('.addPoints').forEach(button => {
    button.addEventListener('click', event => {
        event.preventDefault();

        // Get form data BEFORE disabling elements
        const form = event.target.parentElement;
        const data = new FormData(form);

        const hash = event.target.dataset.hash;
        const addPointsContainer = document.querySelector(`[data-hash="${hash}"] .addpoints`);
        const wait = addPointsContainer.querySelector('.wait');
        const ok = addPointsContainer.querySelector('.ok');
        const formGroup = addPointsContainer.querySelectorAll('select, button');

        formGroup.forEach(item => item.disabled = true);
        wait.style.display = 'block';

        fetch(form.action, {
            method: 'POST',
            body: data,
            cache: 'no-cache',
        })
        .then(response => response.json())
        .then(data => {
            // TODO: Maybe create a binding to the petz array so the above DOM manipulation doesn't need to happen?
            window.petz[hash].pointsRollup = data.rollup;

            let pointsContainer;
            if(window.useCompactView) {
                pointsContainer = document.querySelector(`[data-hash="${hash}"] section .points`);
            } else {
                pointsContainer = document.querySelector(`[data-hash="${hash}"] .points`);
            }

            let rollup = '';
            data.rollup.forEach(type => {
                rollup += `<span class="rank">
                             <b>${type.type}:</b> (${type.total})${type.title ? ` ${type.title}` : ''}
                           </span>`;
            });
            if(rollup !== '') {
                rollup += `<span class="editPointsContainer">
                               <a href="#"><span class="editPoints material-symbols-outlined" data-hash="${hash}">edit_square</span></a>
                           </span>`;
            }
            pointsContainer.innerHTML = rollup;
            pointsContainer.querySelector('.editPoints').addEventListener('click', e => editPointsEvent(e));

            // Update compact view's "summary" line as well.
            if(window.useCompactView) {
                pointsContainer = document.querySelector(`[data-hash="${hash}"] summary .points`);
                rollup = '';
                data.rollup.forEach(type => {
                    rollup += `<span class="rank">
                                 <b>${type.type}:</b> ${type.total}
                               </span>`;
                });
                pointsContainer.innerHTML = rollup;
            }
        })
        .catch(error => {
            // TODO: Show some kind of error dialog thing...
            console.log(error);
        })
        .finally(() => {
            formGroup.forEach(item => item.disabled = false);
            ok.style.display = 'block';
            wait.style.display = 'none';
        });
    });
});

/**
 * Notes
 */
document.querySelectorAll('.showNotes').forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();
        const notes = link.closest('.attributes').querySelector('.notes');
        if(getComputedStyle(notes).getPropertyValue('display') === 'none') {
            link.innerHTML = 'Notes &#9660;';
            const offset = link.parentElement.offsetTop + link.offsetHeight + 4;
            notes.style.top = `${offset}px`;
            notes.style.display = 'block';
        } else {
            link.innerHTML = 'Notes &#9658;';
            notes.style.display = 'none';
        }
    })
});
