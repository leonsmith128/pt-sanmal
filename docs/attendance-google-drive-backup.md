# Attendance selfie backup to Google Drive

## Google Cloud setup

1. Create or select a Google Cloud project and enable the Google Drive API.
2. Configure an OAuth consent screen and create an OAuth client.
3. Generate a refresh token for the dedicated Google account that owns the attendance backup.
4. Grant the OAuth token the `https://www.googleapis.com/auth/drive` scope. This implementation needs to create, inspect, download, and delete only files uploaded by this application, but the Drive API scope is broad; use a dedicated account and restrict access to the destination folder.
5. Create a destination folder in that account and copy its folder ID from the Drive URL.

## Server environment

Set these values in the server environment. Never commit actual credentials:

- `GOOGLE_DRIVE_CLIENT_ID`
- `GOOGLE_DRIVE_CLIENT_SECRET`
- `GOOGLE_DRIVE_REFRESH_TOKEN`
- `GOOGLE_DRIVE_ATTENDANCE_FOLDER_ID`

After changing environment values, refresh Laravel's configuration cache:

```bash
php artisan config:clear
php artisan config:cache
```

## Backup and retention behavior

1. An admin opens the attendance page and selects **Backup ke Drive** for a photo.
2. The server uploads the private photo and fetches Drive metadata.
3. The record is marked `verified` only when the remote byte size and MD5 checksum match the local file.
4. **Hapus lokal** is shown only after that verification. The server checks the local file and re-fetches Drive metadata immediately before deleting the local copy.
5. After local deletion, employee and admin photo preview/download continue through the authenticated application endpoint. The server verifies Drive metadata and the downloaded content before returning it.

There is no automatic deletion, no deletion based on storage pressure, and no deletion when Drive verification fails. A failed backup remains retryable from the admin attendance page.

## Operational notes

- Keep the refresh token in a secret manager or protected server environment file.
- The Google account must retain access to the configured folder.
- Use a shared cache store across application instances so the refreshed access token is reused.
- Backups are per attendance record; this workflow does not grant public access to Drive files.
