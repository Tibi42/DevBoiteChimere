import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'tag', 'tagColor', 'title', 'date', 'btnText', 'btnClass',
        'previewTag', 'previewTitle', 'previewDate', 'previewBtn'
    ];

    connect() {
        this.update();
    }

    update() {
        const tag      = this.tagTarget.value;
        const tagColor = this.tagColorTarget.value;
        const title    = this.titleTarget.value;
        const date     = this.dateTarget.value;
        const btnText  = this.btnTextTarget.value;
        const btnClass = this.btnClassTarget.value;

        this.previewTagTarget.textContent = tag || 'Étiquette';
        this.previewTagTarget.className   = `text-[9px] font-bold uppercase tracking-[0.2em] ${tagColor || 'text-custom-orange'}`;

        this.previewTitleTarget.textContent = title || 'Titre de la slide';
        this.previewDateTarget.textContent  = date  || 'Date';
        this.previewBtnTarget.textContent   = btnText || 'Bouton';
        this.previewBtnTarget.className     = `text-center rounded-lg ${btnClass || 'bg-custom-orange'} px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-900 shadow-lg transition-all`;
    }
}
