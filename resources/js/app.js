import './bootstrap';
import './wallet-adapter';

if (window.config?.YOLIXA_TIP_EXECUTION_MODE === 'soroban' && window.config?.SOROBAN_ENABLED === true) {
    import('./soroban-tip').catch((error) => {
        console.error('Could not load Soroban tipping client.', error);
    });
}
