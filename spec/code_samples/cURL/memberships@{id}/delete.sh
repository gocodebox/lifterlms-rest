curl --request DELETE \
  --url https://example.tld/wp-json/llms/v1/memberships/%7Bid%7D \
  --user ck_XXXXXX:sk_XXXXXX \
  --header 'content-type: application/json' \
  --data '{"force":false}'