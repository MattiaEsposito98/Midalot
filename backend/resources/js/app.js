import './bootstrap';
import '~resources/scss/app.scss';
import '~icons/bootstrap-icons.scss';
import * as bootstrap from 'bootstrap';
import { generaMidalarioPdf } from './midalario-pdf';
import.meta.glob([
    '../img/**'
])

window.generaMidalarioPdf = generaMidalarioPdf;
