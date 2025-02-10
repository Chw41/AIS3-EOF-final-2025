local our = {}

function checker(r)
    local blacklist = { "../", "..\\", ";", "`", "%$", "\"", "|", "<", ">", ",", "{", "}", "[", "]", ".", "'", "%&", "__class__", "--", "#", "\\" }

    for _, word in ipairs(blacklist) do
        if r.args and r.args:find(word) then
            return true
        end
    end

    local user_agent = r.headers_in["User-Agent"]
    if user_agent and user_agent:lower():match("^python%-requests") then
        return true
    end

    local auth_header = r.headers_in["Authorization"]
    if auth_header and auth_header:find("[%c%s]", 1, true) then
        return true
    end

    return false
end

function input_filter(r)
    if checker(r) then
        r:puts("Forbidden\n")
        r.status = 403
        return apache2.FORBIDDEN
    end

    coroutine.yield()
    while bucket do
        local output = bucket
        coroutine.yield(output)
    end
    coroutine.yield()
end

function handle(r)
    if checker(r) then
        r:puts("Forbidden\n")
        r.status = 403
        return apache2.FORBIDDEN
    end

    if r.method == 'POST' then
        local body = r:parsebody()
        for k, v in pairs(body) do
            if v:find("curl") or v:find("rm%s*-rf") then
                r:puts("Forbidden\n")
                r.status = 403
                return apache2.FORBIDDEN
            end
        end
    end

    if r.method == 'GET' then
        r:puts("Hello Lua World!\n")
        for k, v in pairs(r:parseargs()) do
            r:puts(string.format("%s: %s\n", k, v))
        end
    elseif r.method == 'POST' then
        r:puts("Hello Lua World!\n")
        for k, v in pairs(r:parsebody()) do
            r:puts(string.format("%s: %s\n", k, v))
        end
    else
        r:puts("Unsupported HTTP method " .. r.method .. "\n")
        r.status = 405
    end

    return apache2.OK
end
