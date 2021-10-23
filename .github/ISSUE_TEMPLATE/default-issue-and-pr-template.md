---
name: Default issue and PR template
about: Steps to be completed by the developer and build lead before the PR can be
  merged.
title: POWR-
labels: ''
assignees: ''

---

## Developer tasks

- [ ] PR self-review: name includes Jira issue ID and is suitable for creating release notes
- [ ] PR self-review: the files changes are correct 
- [ ] PR self-review: no unexpected files have been included
- [ ] PR self-review: reviewed my choice in machine names with team via chat or issue comments
- [ ] Jira issue is up to date with a suitable use case, implementation details, and test plan
- [ ] CircleCI job passes and there are no merge conflicts at the time of moving my story to QA

## Build lead tasks

- [ ] Review that the above developer tasks are complete
- [ ] PR reviewed for correctness and passing tests
- [ ] Review visual regression test
