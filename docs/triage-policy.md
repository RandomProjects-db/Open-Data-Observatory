# Triage Policy for Web Requests

Applies to requests from the Data, Research, Communications and Engineering teams.

## 1. Intake

All requests come in as GitHub issues, using one of the issue templates: Bug, Feature, Content, SEO, Accessibility. Each template asks for: requesting team, page URL, what you expected, what happened or what you need, and a deadline if one exists. Requests sent by email or chat are redirected to a new issue by the web team.

## 2. Labels

Each issue gets one type label and one priority label.

**Type:** `type: bug`, `type: feature`, `type: content`, `type: seo`, `type: accessibility`.

**Priority and target response times.** Response means a first reply with an owner and next step, not a fix. Business days.

| Priority | Definition | Target response | Target resolution |
|---|---|---|---|
| P1 | Site down, data wrong on a public page, security issue, or content that must be removed now | 1 hour | Same day, or workaround same day |
| P2 | Major feature broken or a blocking accessibility barrier, with no workaround; launch or deadline within a week | 1 business day | [TBD] |
| P3 | Normal improvements, minor bugs with a workaround, routine content and SEO work | 3 business days | Planned into the next cycle |
| P4 | Nice to have, cosmetic, or long-term ideas | 5 business days | Backlog, reviewed monthly |

## 3. Prioritization

Priority is impact multiplied by urgency, each scored 1 to 3.

- **Impact:** 3 = affects all visitors, data accuracy or legal/security risk; 2 = affects a section or a group of users; 1 = affects few people or is cosmetic.
- **Urgency:** 3 = needed within 2 days; 2 = needed within 2 weeks; 1 = no date.

| Score (impact x urgency) | Priority |
|---|---|
| 9 | P1 |
| 6 | P2 |
| 3 to 4 | P3 |
| 1 to 2 | P4 |

The web team sets the first priority. A requester can ask for review by commenting with the reason. Security issues and a public site outage are P1 regardless of score.

## 4. Communication

- The coordinator acknowledges every issue within its response target and names the owner.
- Status is tracked on the GitHub Projects board: Backlog, Ready, In progress, In review, Done.
- The requester is notified in the issue when work moves to staging for review and when it goes to production.
- If a target date will be missed, the owner comments with the new date and the reason.
- P1 incidents also get a direct message to the requesting team and a short written summary in the issue after resolution.
- Priority and backlog are reviewed with the four teams on a regular schedule [TBD].
