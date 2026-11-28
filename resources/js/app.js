import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { initMotion } from './motion';
import onboarding from './components/onboarding';
import mealEditor from './components/meal-editor';
import scanUpload from './components/scan-upload';
import chat from './components/chat';
import water from './components/water';
import calculator from './components/calculator';
import plateDemo from './components/plate-demo';
import chatDemo from './components/chat-demo';
import reportInsight from './components/report-insight';

document.documentElement.classList.add('js');

window.Alpine = Alpine;
Alpine.plugin(collapse);

Alpine.data('onboarding', onboarding);
Alpine.data('mealEditor', mealEditor);
Alpine.data('scanUpload', scanUpload);
Alpine.data('chat', chat);
Alpine.data('water', water);
Alpine.data('calculator', calculator);
Alpine.data('plateDemo', plateDemo);
Alpine.data('chatDemo', chatDemo);
Alpine.data('reportInsight', reportInsight);

Alpine.start();
initMotion();
