# Fake Posts Generator — Contensio Plugin

Generate realistic fake posts for development and testing. Uses [FakerPHP](https://fakerphp.org/) for text content and [Picsum Photos](https://picsum.photos/) for images.

> **Dev only.** This plugin is intended for local and testing environments. A warning banner is shown in the admin UI if the app is running in any other environment.

---

## Features

- Generate any number of fake posts (1–50 per batch via UI, up to 200 via Artisan)
- Choose content type, language, and taxonomy terms to assign
- Fetch and save real photographs from Picsum Photos (1600×900 px, 16:9)
- All fake posts are tagged with a `_fake_post` meta key for easy identification
- One-click "Delete all fake posts" removes posts **and** their associated media files
- Artisan command for CI / scripted environments

---

## Usage

### Admin UI

Navigate to **Settings → Fake Posts** in the Contensio admin panel.

1. Select a **Content type**
2. Select a **Language**
3. Set the **number of posts** (1–50)
4. Optionally enable/disable **image fetching**
5. Optionally check **taxonomy terms** to attach to each post
6. Click **Generate fake posts**

To remove all test content, click **Delete all fake posts** on the same page.

### Artisan command

```bash
php artisan contensio:fake-posts
```

**Options:**

| Option | Default | Description |
|--------|---------|-------------|
| `--count=N` | 10 | Number of posts to generate |
| `--type=post` | first type | Content type name |
| `--terms=1,2,3` | none | Comma-separated term IDs to attach |
| `--no-images` | false | Skip downloading images from Picsum |
| `--delete` | false | Delete all existing fake posts instead of generating |

**Examples:**

```bash
# Generate 20 posts with images
php artisan contensio:fake-posts --count=20

# Generate 5 posts of type "page" without images
php artisan contensio:fake-posts --count=5 --type=page --no-images

# Assign to terms 3, 7, and 12
php artisan contensio:fake-posts --count=10 --terms=3,7,12

# Delete all fake posts and their media
php artisan contensio:fake-posts --delete
```

---

## How it works

Each generated post:

1. Creates a `contents` record (status: `published`, random `published_at` within the past year)
2. Creates a `content_translations` record with Faker title, slug, excerpt, and HTML body paragraphs
3. Attaches selected terms via the `content_terms` pivot table
4. Inserts a `content_meta` record: `meta_key = _fake_post`, `meta_value = 1`

If images are enabled, each post also:

1. Fetches a unique photo from `https://picsum.photos/seed/{random}/1600/900`
2. Saves the file to `storage/app/public/uploads/{year}/{month}/{uuid}.jpg`
3. Creates a `media` record and a `media_translations` record (alt text via Faker)
4. Sets the post's `featured_image_id`

### Deletion

The `_fake_post` meta key is used to identify all fake posts regardless of other attributes. Deleting collects the `featured_image_id` values first, then deletes the content records (cascading to translations, meta, and terms), then deletes the media files from disk and the `media` records.

---

## Requirements

- PHP `fakerphp/faker ^1.23` (declared in `composer.json`)
- Network access to `picsum.photos` for image fetching (skippable with `--no-images`)
