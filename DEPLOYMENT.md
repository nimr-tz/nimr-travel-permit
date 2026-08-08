# Deployment

This app is deployed to the `msmt-02` cluster through Argo CD and `nimr-tz/platform-gitops`.

- Production URL: `https://travel.apps.nimr.or.tz`
- Image: `ghcr.io/mdudaj/nimr-travel-permit`
- GitOps path: `clusters/msmt-02/research/nimr-travel-permit`
- Deploy branch: `master`

On every push to `master`, GitHub Actions runs the test suite, and only if it passes builds the Docker image, pushes `sha-<commit>` and `master` tags to GHCR, and updates the GitOps kustomization image tag. Argo CD then applies the migration hook, web deployment, queue worker, and scheduler CronJob.

## Before a release

Dependency audits are deliberately **not** in CI — a newly published advisory would otherwise fail deploys of unrelated commits. Run them by hand and act on anything they report:

```bash
composer audit
npm audit
```

Required repository secrets:

- `GHCR_USERNAME`
- `GHCR_TOKEN`
- `PLATFORM_GITOPS_APP_ID`
- `PLATFORM_GITOPS_APP_PRIVATE_KEY`
- `SMTP_SERVER`
- `SMTP_PORT`
- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `SMTP_FROM`

Runtime secrets such as `APP_KEY`, `DB_PASSWORD`, and `MAIL_PASSWORD` are managed in GitOps for the cluster deployment.
