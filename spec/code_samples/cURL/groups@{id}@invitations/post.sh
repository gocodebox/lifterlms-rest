curl --request POST \
  --url https://example.tld/wp-json/llms/v1/groups/%7Bid%7D/invitations \
  --user ck_XXXXXX:cs_XXXXXX \
  --header 'content-type: application/json' \
  --data '{"email":"stephen@example.net","role":"member"}'