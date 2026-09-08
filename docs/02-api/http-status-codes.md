# HTTP Status Codes

| Code | Meaning | Project use |
|---|---|---|
| 200 | OK | Successful reads/updates/actions |
| 201 | Created | Successful resource creation |
| 204 | No Content | Successful deletion/empty mutation where applicable |
| 400 | Bad Request | Malformed request where explicitly handled |
| 401 | Unauthorized | Missing/invalid authentication |
| 403 | Forbidden | Policy/permission denied |
| 404 | Not Found | Resource/route not found |
| 409 | Conflict | Business rule or state conflict |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limiting if enabled |
| 500 | Internal Server Error | Unhandled server failure |
