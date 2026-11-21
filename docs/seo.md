# Technical SEO and indexing policy

**Canonical production origin:** https://engleza.wiki  
**Public interface locale:** Romanian (ro-RO)  
**Current language URL strategy:** one Romanian public URL per page. English learning material is the subject matter, not a second localized website. There are no English-prefixed or alternate English URLs.

## Canonical URLs and host policy

- APP_URL must be the canonical HTTPS origin (https://engleza.wiki in production).
- The www alias is permanently redirected by the root Apache rewrite rules to the canonical HTTPS host, including static files. The Laravel middleware also redirects non-canonical request hosts to the configured APP_URL host while preserving the path and query string.
- The root .htaccess removes trailing slashes from non-directory application paths before rewriting requests into public/. This prevents /public/... from leaking into redirect destinations.
- Canonical tags are built from APP_URL and the normalized request path, not the incoming Host header.
- Query strings are excluded from canonical tags. Tracking parameters and filtered listing variants therefore resolve to the clean path.
- A resource's explicit canonical_url overrides the route-derived canonical. Only same-origin canonical resources are included in the XML sitemap; an external canonical target is deliberately not listed.
- The og:url value matches the canonical URL.

## Locale and hreflang

- The public application currently serves Romanian (<html lang="ro">) at unprefixed URLs.
- Indexable pages with a local canonical emit a self-referencing ro-RO alternate only. Pages canonicalized to an external URL do not emit a local hreflang alternate.
- No English hreflang is emitted because there is no distinct English URL variant. x-default is omitted because there is no language selector or language-neutral landing URL.
- If a separately routed English edition is introduced, add stable locale URLs and reciprocal alternates across both editions before emitting en/en-US tags.

## Indexing policy

- A resource is indexable only when it is published, editorially approved, has robots_index=true, and does not declare an external canonical.
- A public test is indexable only when it is published and editorially approved.
- A learning path is indexable only when it has at least one currently available, editorially approved lesson whose linked resource/test activities are also approved and valid.
- Category pages are indexable only when they contain at least one published, approved, index-enabled resource.
- Search results, filtered/paginated listings, authenticated pages, learner results, attempts, private class/teacher pages, and draft policy pages are noindex or excluded from the sitemap.
- Privacy Policy, Terms and Cookie Policy remain draft/noindex until the separate pre-launch legal approval tracked by issue #17 is complete. Do not change privacy.policy_status to published before that approval.
- Grandfathered demo content remains available as placeholder content but is not treated as editorially approved or search-indexable. This does not retroactively approve or rewrite it.
- The sitemap lists only canonical static pages and approved public resources, tests, categories, and valid learning paths. Empty categories and non-indexable records are excluded.
- No ratings, reviews, FAQ claims, or other structured data are fabricated. Structured data should be added only when a truthful, maintained source exists.

## robots.txt

public/robots.txt advertises the canonical sitemap and disallows crawl paths for administration, authenticated learner/teacher areas, attempts, and private result pages. These directives are crawl guidance, not access control. Authentication and authorization remain the security boundary; HTML pages also use noindex where appropriate.

## Crawl and remediation record

Production crawl performed 2026-11-21 before this remediation:

- https://engleza.wiki/, /resources, /learning, /tests, /about, /sitemap.xml, and /robots.txt returned HTTP 200.
- The sitemap contained 59 URLs. All 59 returned HTTP 200, but the sitemap included every active category without checking whether it had approved indexable resources, and included grandfathered demo content.
- A low-concurrency crawl followed 60 unique same-host anchor links discovered across the sitemap pages: all 60 resolved successfully, with zero broken links. Three legacy aliases (/grammar, /vocabulary, /for-teachers) redirected to filtered resource-list URLs; those query variants are now noindex and canonicalize to the clean listing path.
- https://www.engleza.wiki/ served HTTP 200 rather than redirecting to the canonical non-www host. Canonical and Open Graph URLs reflected the incoming host.
- Tracking query parameters were omitted from url()->current() canonical URLs, but the host was not normalized independently of the request.
- /resources/ redirected to /public/resources, exposing the internal public-directory rewrite in the URL.
- Only Romanian is currently served. The old x-default link duplicated the Romanian URL without a language selector or separate default-language destination.

The remediation adds canonical-host redirection, fixes trailing-slash handling before the public-directory rewrite, normalizes canonical/OG URLs, removes the misleading x-default, and restricts sitemap membership and indexability to approved content.

Post-remediation local crawl, 2026-11-21, against the E2E-seeded test database and local Laravel HTTP server:

- XML sitemap parsed successfully: 30 unique URLs, no duplicate locations.
- All 30 sitemap URLs returned HTTP 200, matched their canonical URL and Open Graph URL, had a title and description, declared Romanian as the document language, and were not marked noindex.
- All 30 emitted JSON-LD blocks parsed as valid JSON.
- 49 unique same-origin internal links returned HTTP 200; zero broken links.
- A filtered resource-list query returned HTTP 200 with noindex,nofollow and a clean /resources canonical URL.
- The E2E fixture seeder approves demo fixtures only in the isolated test database. No production content or editorial status was changed.

The local fixture dataset is not directly comparable in size to the live production dataset. Re-run the production crawl after promoting and deploying the change; local CI and the local HTTP crawl cannot verify Hostinger's Apache rewrite behavior or the live CDN redirect chain.

## Repeatable crawl procedure

1. Fetch /robots.txt and verify its sitemap URL uses the canonical HTTPS host.
2. Fetch /sitemap.xml; parse it as XML and verify every loc is absolute, same-origin, unique, canonical, and indexable.
3. Request every sitemap URL and record status, final URL, content type, canonical, robots directive, language, title, description, and hreflang.
4. Verify http://engleza.wiki, https://www.engleza.wiki, and http://www.engleza.wiki converge on https://engleza.wiki.
5. Verify representative trailing-slash routes redirect directly to the slashless path, never to /public/....
6. Verify tracking/filter query variants canonicalize to the clean path and remain out of the sitemap.
7. Verify no English alternate is emitted until a distinct English URL exists.
8. Recheck the sitemap after editorial approvals change; indexability is derived from current publication/editorial state rather than a static snapshot.