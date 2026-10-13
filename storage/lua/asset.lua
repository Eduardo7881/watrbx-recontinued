local assetId = %assetId%
local baseUrl = %baseurl%
local x, y = %width%, %height%

-- Services --
local ContentProvider, ScriptContext, ThumbnailGenerator, InsertService = game:GetService("ContentProvider"), game:GetService("ScriptContext"), game:GetService("ThumbnailGenerator"), game:GetService("InsertService")
if !game:IsLoaded() then repeat wait(0.1) until game:IsLoaded() end

-- Setup
ContentProvider:SetBaseurl(baseUrl)
ScriptContext.ScriptsDisabled = true

-- Shot
local ok, model = pcall(function() return InsertService:LoadAsset(assetId) end)
if not ok or model == nil then return "" end

model.Parent = workspace
return ThumbnailGenerator:Click("PNG", x, y, true)
