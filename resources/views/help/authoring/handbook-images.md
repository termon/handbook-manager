# Handbook Images

Use the **Images** panel in the handbook editor to upload and manage images for a handbook.

## Before You Upload

- Open the handbook you want to edit.
- Switch to the **Images** panel.
- Prepare image files in one of the supported formats: PNG, JPEG, GIF, WebP, or SVG.

## Single Image Upload

Use **Single image upload** when you want more control over one file.

### Steps

1. Select one image file.
2. Add optional alt text.
3. Select **Upload image**.

### When To Use It

- You want to provide custom alt text.
- You may be replacing an existing file and want overwrite confirmation.

### Overwrite Behaviour

- If an image with the same stored filename already exists, the system asks you to confirm the overwrite.
- Choose **Overwrite file** to replace it, or **Cancel** to keep the existing image unchanged.

## Multiple Image Upload

Use **Multiple image upload** when you want to add several images quickly.

### Steps

1. Select one or more image files.
2. Select **Upload images**.
3. Wait for the batch upload to finish.

### How It Works

- Files are uploaded in small batches.
- Existing images with the same stored filename are overwritten automatically.
- Default alt text is taken from each filename without the extension.

## After Upload

- Find the uploaded image in the list on the right.
- Use the **Copy markdown** action to copy the generated markdown snippet.
- Paste that snippet into the page editor where the image should appear.
- Save the page after pasting the copied markdown.

## Using Images In The Editor

1. Upload or confirm the image in the **Images** panel.
2. Select **Copy markdown** for that image.
3. Switch back to the **Markdown editor** panel.
4. Paste the copied image tag into the page body.
5. Save the page.

## Unsaved Page Changes

- Copying markdown from the Images panel does not save the page for you.
- If you navigate to a different page or a different handbook before saving, your unsaved editor changes are discarded.
- Save immediately after inserting a copied image tag if you want to keep the change.

## Tips

- Use the single-file form if you need custom alt text.
- Use the multi-file form for bulk uploads where filename-based alt text is acceptable.
- Keep filenames stable if you want to update an image without changing the markdown path already used in pages.
