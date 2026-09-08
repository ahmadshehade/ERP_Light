# Media and File Uploads

The project uses Spatie Media Library for model media and Pion Laravel Chunk Upload for chunked large-file uploads.

## Important development issue

Spatie Media Library's default size constraints caused rejection of a file around 253 MB because the effective limit was lower than the attempted upload.

## Design considerations

- Configure media limits intentionally for the deployment requirements.
- Use chunked uploads for large files where appropriate.
- Validate file type and size at the API boundary.
- Store media in tenant-aware storage where the model belongs to a tenant.
- Test upload failure and cleanup paths.

## Tenant schema dependency

Tenant media operations require the tenant's `media` table to exist in the tenant database. A missing tenant media table previously caused runtime SQL errors.
