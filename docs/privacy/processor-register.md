# Processor and recipient register

**Status:** preliminary engineering register, not a verified list of contracted processors or subprocessors. Verify invoices, contracts, DPAs, DNS, production configuration and provider dashboards before approval.

| Provider / system | Evidence in current implementation | Data potentially exposed | Required verification |
|---|---|---|---|
| Hostinger hosting and CDN | Production site hosted on Hostinger; CDN enabled; deployment audit verified CDN/origin topology | Account/database, uploaded media, requests, IP/technical logs and backups | Legal entity, hosting region, DPA, subprocessors, support access, CDN processing, backup locations/retention and deletion support |
| Bunny Fonts | Public layout requests fonts from fonts.bunny.net | Browser IP, user-agent and font request metadata | Legal entity, terms/DPA, region, retention and self-hosting option |
| SMTP provider | Authenticated SMTP is configurable; actual production provider is not identifiable from source | Account email, verification/reset message metadata and content | Identify provider from production configuration without exposing credentials; verify DPA, region and retention |
| Laravel/database/storage | MySQL/MariaDB, sessions/cache/queues and local/public storage are application-configured | Account, learning, assessment, writing, class, session and operational data | Verify actual production locations, encryption, access, backup and log policies |
| Analytics/advertising | No analytics or advertising tags found in this repository revision | No data through repository-installed tags at this revision | Inspect production GTM and injected scripts before launch; repository absence alone does not prove production absence |

Do not add a processor or enable analytics until purpose, data categories, location, DPA/transfer mechanism, retention, access and user-facing notice/consent requirements are documented.
