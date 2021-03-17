# SwaggerConverter

轉換 apidoc json 文件至 swagger json 格式.

本專案取得Jenkins apidoc json 檔的方式為呼叫 Jenkins html report plugin 所產生的 apidoc 文件

若有需要請自行更改取得 Json 部分 (請看 task 的程式就會知道了...)


## 使用方式

```=bash
php artisan swagger:update
```
更新完畢後開啟 swagger-doc/index.html 即可