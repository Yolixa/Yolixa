import './bootstrap';

if (window.config?.YOLIXA_TIP_EXECUTION_MODE === 'soroban') {
    import('./soroban-tip').catch((error) => {
        console.error('Could not load Soroban tipping client.', error);
    });
}
