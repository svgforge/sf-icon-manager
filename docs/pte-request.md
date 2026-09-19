# PTE Request for the German Locale

To get the German translations on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/sf-icon-manager/) approved (moved from *Waiting* to *Current*), the plugin author needs **PTE** (Project Translation Editor) access for this plugin in the German locale. Language packs are only built from approved translations.

## Alternative: ask the German team

Log in at https://make.wordpress.org/polyglots/ and publish a post as described below, then the German GTE/PTEs can review and approve the 71 `Waiting` strings directly. The PTE request is more reliable, because it lets you approve future strings yourself.

## 1. Post a PTE request

1. Log in to https://wordpress.org/
2. Go to https://make.wordpress.org/polyglots/
3. Publish a new post (`+ Write`) using the template below
4. Make sure the tags `#editor-requests` and `#de` are set
5. Replace `@your-wp-username` with your WordPress.org username

Expected turnaround: usually 1–2 days. Once granted, you are listed as PTE for the plugin.

## Template

```text
I am the plugin author for sf-icon-manager (https://wordpress.org/plugins/sf-icon-manager/).

I have translated the plugin into German on translate.wordpress.org and would like to request PTE access for the German locale so I can review and approve translations as I maintain them.

Please add: #de — @your-wp-username

#editor-requests
```

## 2. Approve the waiting translations

After PTE access is granted:

1. Go to https://translate.wordpress.org/projects/wp-plugins/sf-icon-manager/stable/de/default/
2. Filter by status → `Waiting`
3. Open each string and click **Approve** (moves it to *Current*), or
   re-import `languages/sf-icon-manager-de_DE.po` and tick **Set as current** in the import form

## 3. Import the trunk project

Repeat the .po import for the development set so both are complete:

- https://translate.wordpress.org/projects/wp-plugins/sf-icon-manager/dev/de/default/
- Upload `languages/sf-icon-manager-de_DE.po`, tick **Set as current**

## Language pack generation

WordPress.org generates the German language pack automatically once ≥90% of the strings are *Current* in the relevant set(s). After that, German users get the translations without any plugin upgrade.