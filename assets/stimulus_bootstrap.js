import { startStimulusApp } from '@symfony/stimulus-bundle';
import CarouselPreviewController from './controllers/carousel_preview_controller.js';

const app = startStimulusApp();
app.register('carousel-preview', CarouselPreviewController);
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
