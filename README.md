# exment-official-plugins

The free official plugins of [Exment](https://github.com/exceedone/exment), bundled with it (ISS00419):

- ExmentPM
- IT Support (ExmentITSupport)
- Groupware (ExmentGroupware)

Exment requires this package, so the plugins come with every Exment, also in the zip for the sites that are not on the internet. **Nothing of them is installed, loaded or made (no tables) until a system administrator chooses "Use"** in Admin setting > Feature management. A newer version is only shown there; it is never installed by itself, and the version before an update is kept to go back to.

## Contents

```
releases/<name>-<version>.zip    the plugin, as it is uploaded on the plugin page
releases/<name>-<version>.json   its release information, signed (format "exment-plugin-release/1")
```

Exment checks each release before it shows or installs it: the Ed25519 signature of the JSON (with a key Exment trusts, `release-<year>-<n>`), the sha256 and the size of the zip, the uuid and the version of its `config.json`, and the versions of Exment and PHP it needs (`exment_requires`, `php_requires`). A release that does not pass is not shown.

The CI of [exment-plugins](https://github.com/exceedone/exment-plugins) builds the zip and its record (not signed) when a version is tagged, and opens a pull request here. **The key is only on the computer of the person who signs** (never on GitHub or a server). To sign a pull request:

```
git fetch origin release/<name>-<version> && git checkout release/<name>-<version>
php tools/release.php sign releases/<name>-<version>.json <key-folder>/release-2026-1.key && git commit -am sign && git push
```

Exment shows and installs only the releases signed with a key it trusts. The key is made once, on that computer: `php tools/release.php key release-2026-1 <key-folder>/release-2026-1.key` (it prints the public key that goes into Exment).

The versions of this package follow the minor versions of Exment: Exment 7.2 requires `~7.2.0`.

## License

GPL-3.0-or-later, as Exment.

---

**日本語**: Exment の無料の公式プラグイン（ExmentPM・ITサポート・Groupware）を、署名した形で Exment に同梱するためのパッケージです。Exment が必ず読み込みますが、管理者が「機能の管理」で「使う」を押すまでは、インストールも読み込みもしません（テーブルも作りません）。新しい版はお知らせだけで、自動では入れず、前の版に戻せます。zip と署名していない版の情報は、exment-plugins の CI がタグを付けたときに作り、ここに PR を出します。署名の鍵は署名する人の手元だけにあり（GitHub やサーバーには置かない）、手元で `php tools/release.php sign ...` を 1 行動かして署名し、push します。署名のない版は、Exment は出さず、入れません。

**Tiếng Việt**: Gói chứa các plugin chính thức miễn phí của Exment (ExmentPM, IT Support, Groupware) dưới dạng bản phát hành đã ký. Exment luôn tải gói này, nhưng không cài đặt hay nạp plugin nào cho đến khi quản trị viên chọn "Dùng" trong "Quản lý chức năng". Phiên bản mới chỉ được thông báo, không tự cài, và có thể quay lại phiên bản trước.
