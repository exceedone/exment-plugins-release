# exment-plugins-release

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

The CI of [exment-plugins](https://github.com/exceedone/exment-plugins) builds the zip and its record (not signed) when a version is tagged, and pushes the branch `release/<name>-<version>` with a pull request here.

**Signing (since 2026-11-27): automatic, in GitHub Actions.** The workflow `.github/workflows/sign.yml` runs on every push to `release/**` that touches `releases/*.json` (or by hand: Actions → sign → Run workflow on a `release/...` branch). It signs each record that has no signature with `php tools/release.php sign`, then commits `sign: <name>-<version> (<key_id>)` to the same branch. A person (or the release Claude session) then checks the pull request and merges it.

- The key is the environment secret `RELEASE_SIGNING_KEY` of the environment `release-signing`, which only `release/*` branches can use. It is either the whole key file (`{"key_id":"release-2026-1","secret_key":"..."}`) or only its base64 `secret_key`. No reviewer is required (decided by the owner on 2026-11-27).
- The workflow writes the key to a temporary file (mode 600), checks that its public key is one Exment trusts (`ReleaseVerifier::TRUSTED_KEYS`), never prints any part of it, and removes the file. If the key is refused it prints only why (empty, not JSON, untrusted key_id, wrong length, public key mismatch, or the shape of an unreadable value).
- Only the owner puts or replaces the secret (Settings → Environments → release-signing). Nobody can read it back. Claude sessions never see the key.
- The key file stays on the owner's computer as the master copy, with an offline copy.

Manual signing still works as a fallback, on the computer that has the key:

```
git fetch origin release/<name>-<version> && git checkout release/<name>-<version>
php tools/release.php sign releases/<name>-<version>.json <key-folder>/release-2026-1.key && git commit -am sign && git push
```

Exment shows and installs only the releases signed with a key it trusts. The key is made once, on that computer: `php tools/release.php key release-2026-1 <key-folder>/release-2026-1.key` (it prints the public key that goes into Exment). The spare key `release-2026-2` is trusted too, for the day the first one is lost.

The versions of this package follow the minor versions of Exment: Exment 7.2 requires `~7.2.0`.

## License

GPL-3.0-or-later, as Exment.

---

**日本語**: Exment の無料の公式プラグイン（ExmentPM・ITサポート・Groupware）を、署名した形で Exment に同梱するためのパッケージです。Exment が必ず読み込みますが、管理者が「機能の管理」で「使う」を押すまでは、インストールも読み込みもしません（テーブルも作りません）。新しい版はお知らせだけで、自動では入れず、前の版に戻せます。zip と署名していない版の情報は、exment-plugins の CI がタグを付けたときに作り、ここに PR を出します。**署名は 2026-11-27 から GitHub Actions で自動です**（`.github/workflows/sign.yml`）。`release/*` のブランチに版の情報が来ると、environment `release-signing` の secret `RELEASE_SIGNING_KEY` で署名して、同じブランチに `sign: …` をコミットします（承認は要らない、と持ち主が決めた）。鍵は一時ファイルにだけ書き、Exment が信じる公開鍵と合うかを確かめ、表に出さず、消します。secret を入れる・入れかえるのは持ち主だけで、だれも読み出せません（Claude も鍵を見ません）。鍵の元は持ち主の PC とオフラインの写しにあり、手元で `php tools/release.php sign ...` を動かすやり方も、予備として使えます。署名のない版は、Exment は出さず、入れません。

**Tiếng Việt**: Gói chứa các plugin chính thức miễn phí của Exment (ExmentPM, IT Support, Groupware) dưới dạng bản phát hành đã ký. Exment luôn tải gói này, nhưng không cài đặt hay nạp plugin nào cho đến khi quản trị viên chọn "Dùng" trong "Quản lý chức năng". Phiên bản mới chỉ được thông báo, không tự cài, và có thể quay lại phiên bản trước.
