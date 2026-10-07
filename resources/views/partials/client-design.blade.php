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
    body.site-app .balance-daily {
        background: #4e2430 !important;
        color: #f7f5f2 !important;
        --bs-card-bg: #4e2430;
        --bs-card-color: #f7f5f2;
    }

    body.page-portfolio {
        background: #f6f3ef !important;
        overflow-x: hidden;
    }
    body.page-portfolio .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 72px;
        padding: 12px 20px !important;
        background: rgba(255, 252, 248, 0.94) !important;
        border-bottom: 1px solid #ece7df;
    }
    body.page-portfolio .brand-logo {
        height: 40px;
        width: auto;
    }
    body.page-portfolio .wrap.page {
        width: min(1080px, calc(100% - 32px));
        padding-top: 22px;
        padding-bottom: calc(108px + env(safe-area-inset-bottom));
    }
    body.page-portfolio .live-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 2px 0 18px !important;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
    }
    body.page-portfolio .portfolio-page {
        display: flex;
        flex-direction: column;
        gap: 22px;
        min-width: 0;
        font-size: 0.95rem;
    }
    body.page-portfolio .portfolio-head { margin: 0; }
    body.page-portfolio .portfolio-title {
        margin: 0 !important;
        font-size: clamp(2rem, 7vw, 2.7rem) !important;
        line-height: 1.02 !important;
        letter-spacing: -0.03em;
        color: #141414;
    }
    body.page-portfolio .portfolio-subtitle {
        margin: 0;
        font-size: 1.35rem;
        line-height: 1.15;
        color: #141414;
    }

    body.page-portfolio .daily-revenue-card {
        width: 100%;
        margin: 0 !important;
        padding: 20px 18px !important;
        background: #4e2430 !important;
        color: #f7f5f2 !important;
        border: 0 !important;
        border-radius: 16px !important;
        box-shadow: 0 12px 28px rgba(78, 36, 48, 0.16);
    }
    body.page-portfolio .daily-main {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }
    body.page-portfolio .daily-icon {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.1);
        color: #e7dcc4;
        font-size: 1.15rem;
    }
    body.page-portfolio .daily-copy { min-width: 0; }
    body.page-portfolio .daily-revenue-card .daily-label {
        display: block;
        margin: 0;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(247, 245, 242, 0.82) !important;
    }
    body.page-portfolio .daily-revenue-card strong {
        display: block;
        margin: 8px 0 0;
        font-family: "Fraunces", Georgia, serif;
        font-size: clamp(1.7rem, 6.2vw, 2.15rem) !important;
        font-weight: 560;
        line-height: 1.05;
        color: #ffffff !important;
        letter-spacing: -0.03em;
    }
    body.page-portfolio .daily-note {
        display: grid;
        gap: 6px;
        margin: 16px 0 0;
        padding-top: 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.16);
    }
    body.page-portfolio .daily-note p {
        margin: 0;
        font-size: 0.86rem;
        line-height: 1.45;
        color: rgba(247, 245, 242, 0.92) !important;
        overflow-wrap: anywhere;
    }

    body.page-portfolio .portfolio-services {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin: 0 !important;
        min-width: 0;
    }
    body.page-portfolio a.portfolio-service {
        display: flex !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 0;
        min-height: 104px;
        margin: 0;
        padding: 16px 8px !important;
        border-radius: 16px !important;
        color: #ffffff !important;
        text-align: center;
        text-decoration: none !important;
        box-shadow: 0 10px 22px rgba(17, 17, 17, 0.12);
    }
    a.portfolio-service.service-deposit { background: #9f2d2d !important; --bs-card-bg: #9f2d2d; }
    body.page-portfolio a.portfolio-service.service-withdraw { background: #000000 !important; --bs-card-bg: #000000; }
    body.page-portfolio a.portfolio-service.service-transfer { background: #6f2338 !important; --bs-card-bg: #6f2338; }
    body.page-portfolio a.portfolio-service i {
        display: block;
        font-size: 1.25rem;
        line-height: 1;
        color: #e7dcc4 !important;
        text-decoration: none !important;
    }
    body.page-portfolio a.portfolio-service strong,
    body.page-portfolio a.portfolio-service span {
        display: block;
        max-width: 100%;
        color: #ffffff !important;
        text-decoration: none !important;
    }
    body.page-portfolio a.portfolio-service strong {
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: 0;
        white-space: nowrap !important;
        word-break: keep-all;
        overflow-wrap: normal;
    }
    body.page-portfolio a.portfolio-service span {
        font-size: 0.68rem;
        font-weight: 600;
        line-height: 1.3;
        color: rgba(255, 255, 255, 0.78) !important;
        white-space: normal;
    }

    body.page-portfolio .portfolio-empty {
        display: grid;
        justify-items: center;
        gap: 8px;
        margin: 0;
        padding: 28px 20px;
        background: #ffffff;
        border: 1px solid #ece7df;
        border-radius: 16px;
        text-align: center;
        box-shadow: 0 8px 22px rgba(17, 17, 17, 0.04);
    }
    body.page-portfolio .portfolio-empty i {
        display: grid;
        place-items: center;
        width: 46px;
        height: 46px;
        margin-bottom: 4px;
        border-radius: 14px;
        background: #f8f1ea;
        color: #9f2d2d;
        font-size: 1.25rem;
    }
    body.page-portfolio .portfolio-empty h2,
    body.page-portfolio .portfolio-empty p {
        margin: 0;
        max-width: 28rem;
    }
    body.page-portfolio .portfolio-empty h2 {
        font-family: "Manrope", "Segoe UI", sans-serif;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: 0;
        color: #141414;
    }
    body.page-portfolio .portfolio-empty p {
        color: #6d675f;
        font-size: 0.88rem;
        line-height: 1.45;
    }
    body.page-portfolio a.portfolio-empty-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 8px;
        padding: 11px 16px;
        border-radius: 999px;
        background: #111111;
        color: #ffffff !important;
        font-size: 0.86rem;
        font-weight: 800;
        text-decoration: none !important;
    }
    body.page-portfolio .portfolio-empty-soft {
        padding: 26px 18px;
        background: #fffdfb;
    }

    body.page-portfolio .portfolio-positions,
    body.page-portfolio .profit-history,
    body.page-portfolio .profit-head {
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-width: 0;
    }
    body.page-portfolio .profit-lead {
        margin: 0;
        color: #6d675f;
        font-size: 0.86rem;
        line-height: 1.4;
    }
    body.page-portfolio .portfolio-holdings {
        display: grid;
        gap: 12px;
    }
    body.page-portfolio .holding-card {
        padding: 16px;
        background: #ffffff;
        border: 1px solid #ece7df;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(17, 17, 17, 0.04);
    }
    body.page-portfolio .holding-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }
    body.page-portfolio .holding-name {
        color: #141414;
        font-weight: 800;
        line-height: 1.3;
        text-decoration: none;
    }
    body.page-portfolio .holding-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 14px;
        margin: 0;
    }
    body.page-portfolio .holding-meta dt {
        margin: 0;
        color: #6d675f;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    body.page-portfolio .holding-meta dd {
        margin: 2px 0 0;
        color: #141414;
        font-size: 0.92rem;
        font-weight: 800;
    }
    body.page-portfolio .holding-wide { grid-column: 1 / -1; }
    body.page-portfolio .portfolio-table-wrap { display: none; }
    body.page-portfolio .portfolio-table {
        width: 100%;
        border-collapse: collapse;
        background: #ffffff;
        border: 1px solid #ece7df;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(17, 17, 17, 0.04);
    }
    body.page-portfolio .portfolio-table th,
    body.page-portfolio .portfolio-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #ece7df;
        text-align: left;
        vertical-align: middle;
    }
    body.page-portfolio .portfolio-table th {
        color: #6d675f;
        font-size: 0.72rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    body.page-portfolio .portfolio-table a {
        color: #141414;
        font-weight: 800;
        text-decoration: none;
    }
    body.page-portfolio .txn {
        border-radius: 16px;
        padding: 16px 18px;
        box-shadow: 0 8px 18px rgba(17, 17, 17, 0.04);
    }
    body.page-portfolio .portfolio-pages { min-width: 0; }
    body.page-portfolio .pagination { margin: 0; flex-wrap: wrap; }

    @media (max-width: 900px) {
        body.page-portfolio .topbar {
            display: grid !important;
            grid-template-columns: minmax(72px, 1fr) auto minmax(72px, 1fr);
            align-items: center;
            column-gap: 12px;
            min-height: 68px;
            padding: 10px 16px !important;
        }
        body.page-portfolio .topbar .corner-btn[data-back] {
            justify-self: start;
            padding: 8px 12px;
            min-height: 40px;
            font-size: 0.82rem;
        }
        body.page-portfolio .topbar .brand {
            position: static !important;
            left: auto !important;
            transform: none !important;
            justify-self: center;
            gap: 0;
        }
        body.page-portfolio .topbar .nav-toggle {
            justify-self: end;
            padding: 8px 12px;
            min-height: 40px;
            font-size: 0.82rem;
        }
        body.page-portfolio .brand-logo { height: 36px; }
        body.page-portfolio .bottom-nav {
            display: flex !important;
            left: 10px !important;
            right: 10px !important;
            bottom: calc(10px + env(safe-area-inset-bottom)) !important;
            height: 64px;
            min-height: 64px;
            align-items: stretch;
            padding: 6px !important;
            border-radius: 18px !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2);
        }
        body.page-portfolio .bottom-nav a {
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            min-width: 0;
            padding: 4px 2px !important;
            border-radius: 12px;
            color: #c9cdd6 !important;
            font-size: clamp(0.56rem, 2.55vw, 0.68rem) !important;
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -0.02em;
            text-decoration: none !important;
            white-space: nowrap;
        }
        body.page-portfolio .bottom-nav strong {
            display: block;
            color: inherit;
            font-size: 1rem;
            font-weight: 500;
            line-height: 1;
        }
        body.page-portfolio .bottom-nav a.active {
            color: #ffffff !important;
            background: #9f2d2d;
        }
    }

    @media (max-width: 389px) {
        body.page-portfolio .wrap.page { width: calc(100% - 24px); }
        body.page-portfolio .portfolio-page { gap: 18px; }
        body.page-portfolio .daily-revenue-card { padding: 18px 16px !important; }
        body.page-portfolio .portfolio-services { gap: 8px; }
        body.page-portfolio a.portfolio-service {
            min-height: 92px;
            padding: 14px 4px !important;
            gap: 7px;
        }
        body.page-portfolio a.portfolio-service span { display: none !important; }
        body.page-portfolio a.portfolio-service strong { font-size: 0.68rem !important; }
        body.page-portfolio .topbar {
            padding: 10px 12px !important;
            column-gap: 8px;
        }
    }

    @media (min-width: 768px) {
        body.page-portfolio .portfolio-holdings { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        body.page-portfolio .portfolio-services { gap: 16px; }
        body.page-portfolio a.portfolio-service { min-height: 116px; padding: 18px 12px !important; }
        body.page-portfolio a.portfolio-service span { display: block !important; }
    }

    @media (min-width: 992px) {
        body.page-portfolio .wrap.page { padding-bottom: 48px; }
        body.page-portfolio .portfolio-holdings { display: none; }
        body.page-portfolio .portfolio-table-wrap {
            display: block;
            overflow-x: auto;
            border-radius: 16px;
        }
    }
</style>
