<style>
    body.site-app .balance-card {
        color: #f7f5f2 !important;
        border-radius: 0.8rem !important;
    }
    body.site-app .balance-invested { background: #111111 !important; --bs-card-bg: #111111; }
    body.site-app .balance-available { background: #9f2d2d !important; --bs-card-bg: #9f2d2d; }
    body.site-app .balance-profit { background: #6e2430 !important; --bs-card-bg: #6e2430; }
    body.site-app .balance-bonus { background: #6d4a32 !important; --bs-card-bg: #6d4a32; }
    body.site-app .balance-commission { background: #7a2e2e !important; --bs-card-bg: #7a2e2e; }
    body.site-app .balance-total { background: linear-gradient(160deg, #141414, #6e2430) !important; --bs-card-bg: #141414; }
    body.site-app .balance-pending { background: #2a211c !important; --bs-card-bg: #2a211c; }
    body.site-app .balance-paid { background: #3d2424 !important; --bs-card-bg: #3d2424; }
    body.site-app .balance-daily,
    body.site-app .daily-revenue-card {
        background: #4e2430 !important;
        color: #f7f5f2 !important;
        --bs-card-bg: #4e2430;
        --bs-card-color: #f7f5f2;
        border-radius: 0.85rem !important;
    }
    body.site-app .daily-revenue-card .daily-label {
        display: block;
        font-size: 0.66rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        opacity: 0.86;
    }
    body.site-app .daily-revenue-card strong {
        display: block;
        font-size: 1.05rem;
        line-height: 1.15;
        color: #f7f5f2 !important;
    }
    body.site-app .daily-revenue-card .daily-note {
        margin: 0.45rem 0 0;
        font-size: 0.72rem;
        line-height: 1.35;
        color: #f7f5f2 !important;
    }
    body.site-app a.portfolio-service {
        display: block;
        background-color: #111111;
        color: #ffffff !important;
        text-decoration: none !important;
        border-radius: 0.8rem !important;
        --bs-card-color: #ffffff;
    }
    body.site-app a.portfolio-service.service-deposit { background: #9f2d2d !important; --bs-card-bg: #9f2d2d; }
    body.site-app a.portfolio-service.service-withdraw { background: #111111 !important; --bs-card-bg: #111111; }
    body.site-app a.portfolio-service.service-transfer { background: #6e2430 !important; --bs-card-bg: #6e2430; }
    body.site-app a.portfolio-service,
    body.site-app a.portfolio-service strong,
    body.site-app a.portfolio-service span {
        color: #ffffff !important;
        text-decoration: none !important;
    }
    body.site-app a.portfolio-service i { color: #e7dcc4 !important; text-decoration: none !important; }
    body.site-app a.portfolio-service strong {
        display: block;
        font-size: 0.72rem;
        line-height: 1.15;
        white-space: nowrap !important;
        word-break: keep-all;
        overflow-wrap: normal;
    }
    body.site-app a.portfolio-service .card-body { text-align: center; padding: 0.55rem 0.3rem; }
    body.site-app .portfolio-title { font-size: clamp(1.45rem, 6.5vw, 2.15rem); line-height: 1.1; }
    @media (max-width: 420px) {
        body.site-app a.portfolio-service span { display: none !important; }
        body.site-app a.portfolio-service strong { font-size: 0.68rem !important; }
    }
</style>
