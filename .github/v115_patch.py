from pathlib import Path

root = Path('src/addons/Warext/MinecraftVote')

template = root / '_output/templates/public/warext_mc_server_index.html'
text = template.read_text(encoding='utf-8')
text = text.replace('warextMcVoteList', 'warextMcDirectoryList')
text = text.replace('warextMcVoteRow', 'warextMcDirectoryRow')
template.write_text(text, encoding='utf-8')

less = root / '_output/templates/public/warext_mc_server_directory.less'
less.write_text(r'''.warextMcDirectoryList
{
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.warextMcDirectoryRow
{
    display: flex;
    align-items: stretch;
    width: 100%;
    min-width: 0;
    min-height: 132px;
    border: 1px solid @xf-borderColor;
    border-radius: @xf-borderRadiusMedium;
    overflow: hidden;
    background: @xf-contentBg;
    box-sizing: border-box;
}

.warextMcDirectoryRow-rank
{
    flex: 0 0 72px;
    min-width: 72px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 3px;
    padding: 12px 8px;
    border-right: 1px solid @xf-borderColor;
    background: @xf-contentAltBg;
    box-sizing: border-box;
}

.warextMcDirectoryRow-rank span
{
    font-size: @xf-fontSizeSmallest;
    color: @xf-textColorMuted;
    text-transform: uppercase;
}

.warextMcDirectoryRow-rank strong
{
    font-size: 20px;
    line-height: 1;
}

.warextMcDirectoryRow-media
{
    flex: 0 0 316px;
    width: 316px;
    min-width: 316px;
    padding: 16px 0 16px 16px;
    display: flex;
    align-items: center;
    box-sizing: border-box;
}

.warextMcDirectoryRow-media img,
.warextMcDirectoryRow-mediaFallback
{
    display: block;
    width: 300px;
    height: 100px;
    max-width: 100%;
    border: 1px solid @xf-borderColor;
    border-radius: @xf-borderRadiusMedium;
    box-sizing: border-box;
}

.warextMcDirectoryRow-media img
{
    object-fit: cover;
}

.warextMcDirectoryRow-mediaFallback
{
    display: flex;
    align-items: center;
    justify-content: center;
    background: @xf-contentAltBg;
    color: @xf-textColorMuted;
    font-size: 26px;
}

.warextMcDirectoryRow-main
{
    flex: 1 1 auto;
    width: auto;
    min-width: 0;
    padding: 16px;
    box-sizing: border-box;
    overflow: hidden;
}

.warextMcDirectoryRow-head
{
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    min-width: 0;
}

.warextMcDirectoryRow-titleWrap
{
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
    min-width: 0;
}

.warextMcDirectoryRow-title
{
    min-width: 0;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.warextMcDirectoryRow-badges,
.warextMcDirectoryRow-tags
{
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
}

.warextMcDirectoryRow-ping
{
    flex: 0 0 auto;
    padding: 3px 7px;
    border-radius: 999px;
    background: @xf-contentAltBg;
    color: @xf-textColorMuted;
    font-size: @xf-fontSizeSmallest;
}

.warextMcDirectoryRow-motd
{
    margin-top: 7px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    color: @xf-textColorMuted;
}

.warextMcDirectoryRow-tags
{
    margin-top: 9px;
}

.warextMcDirectoryRow-tags > span
{
    padding: 3px 7px;
    border: 1px solid @xf-borderColor;
    border-radius: 999px;
    background: @xf-contentAltBg;
    font-size: @xf-fontSizeSmallest;
}

.warextMcDirectoryRow-bottom
{
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 14px;
    min-width: 0;
    margin-top: 12px;
}

.warextMcDirectoryRow-address
{
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
}

.warextMcDirectoryRow-address > span
{
    flex: 0 0 100%;
    font-size: @xf-fontSizeSmallest;
    color: @xf-textColorMuted;
}

.warextMcDirectoryRow-address strong
{
    min-width: 0;
    font-family: monospace;
    overflow-wrap: anywhere;
}

.warextMcDirectoryRow-metrics
{
    flex: 0 0 auto;
    display: flex;
    align-items: stretch;
    gap: 5px;
}

.warextMcDirectoryRow-metrics > div
{
    min-width: 64px;
    padding: 6px 7px;
    text-align: center;
    border: 1px solid @xf-borderColor;
    border-radius: @xf-borderRadiusMedium;
    background: @xf-contentAltBg;
    box-sizing: border-box;
}

.warextMcDirectoryRow-metrics strong,
.warextMcDirectoryRow-metrics span
{
    display: block;
}

.warextMcDirectoryRow-metrics small
{
    font-size: @xf-fontSizeSmallest;
    font-weight: 400;
    color: @xf-textColorMuted;
}

.warextMcDirectoryRow-metrics span
{
    margin-top: 3px;
    font-size: @xf-fontSizeSmallest;
    color: @xf-textColorMuted;
}

.warextMcDirectoryRow-actions
{
    flex: 0 0 138px;
    width: 138px;
    min-width: 138px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: stretch;
    gap: 8px;
    padding: 14px;
    border-left: 1px solid @xf-borderColor;
    background: @xf-contentAltBg;
    text-align: center;
    box-sizing: border-box;
}

.warextMcDirectoryRow-votes strong,
.warextMcDirectoryRow-votes span
{
    display: block;
}

.warextMcDirectoryRow-votes strong
{
    font-size: 21px;
    line-height: 1.1;
}

.warextMcDirectoryRow-votes span,
.warextMcDirectoryRow-detailLink
{
    font-size: @xf-fontSizeSmallest;
}

.warextMcDirectoryRow-votes span
{
    margin-top: 2px;
    color: @xf-textColorMuted;
}

.warextMcDirectoryRow-voteButton
{
    width: 100%;
}

@media (max-width: 1100px)
{
    .warextMcDirectoryRow
    {
        flex-wrap: wrap;
    }

    .warextMcDirectoryRow-rank
    {
        flex-basis: 64px;
        min-width: 64px;
    }

    .warextMcDirectoryRow-media
    {
        flex: 0 0 256px;
        width: 256px;
        min-width: 256px;
        padding: 12px 0 12px 12px;
    }

    .warextMcDirectoryRow-media img,
    .warextMcDirectoryRow-mediaFallback
    {
        width: 240px;
        height: 80px;
    }

    .warextMcDirectoryRow-main
    {
        flex: 1 1 calc(100% - 320px);
    }

    .warextMcDirectoryRow-actions
    {
        flex: 0 0 100%;
        width: 100%;
        min-width: 0;
        flex-direction: row;
        align-items: center;
        border-left: 0;
        border-top: 1px solid @xf-borderColor;
    }

    .warextMcDirectoryRow-votes
    {
        flex: 0 0 76px;
    }

    .warextMcDirectoryRow-voteButton
    {
        flex: 1 1 auto;
        width: auto;
    }
}

@media (max-width: @xf-responsiveNarrow)
{
    .warextMcDirectoryRow
    {
        display: block;
        min-height: 0;
    }

    .warextMcDirectoryRow-rank
    {
        width: 100%;
        min-width: 0;
        flex-direction: row;
        justify-content: flex-start;
        padding: 9px 12px;
        border-right: 0;
        border-bottom: 1px solid @xf-borderColor;
    }

    .warextMcDirectoryRow-rank strong
    {
        font-size: 15px;
    }

    .warextMcDirectoryRow-media
    {
        width: 100%;
        min-width: 0;
        padding: 12px;
    }

    .warextMcDirectoryRow-media img,
    .warextMcDirectoryRow-mediaFallback
    {
        width: 100%;
        height: auto;
        aspect-ratio: 3 / 1;
    }

    .warextMcDirectoryRow-main
    {
        width: 100%;
        padding: 12px;
    }

    .warextMcDirectoryRow-head,
    .warextMcDirectoryRow-bottom
    {
        align-items: stretch;
        flex-direction: column;
    }

    .warextMcDirectoryRow-metrics
    {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .warextMcDirectoryRow-metrics > div
    {
        min-width: 0;
    }

    .warextMcDirectoryRow-actions
    {
        width: 100%;
        min-width: 0;
        flex-direction: row;
        align-items: center;
        border-left: 0;
        border-top: 1px solid @xf-borderColor;
    }

    .warextMcDirectoryRow-voteButton
    {
        flex: 1 1 auto;
        width: auto;
    }
}

@media (max-width: 480px)
{
    .warextMcDirectoryRow-actions
    {
        flex-wrap: wrap;
    }

    .warextMcDirectoryRow-detailLink
    {
        width: 100%;
    }
}
''', encoding='utf-8')

addon = root / 'addon.json'
text = addon.read_text(encoding='utf-8')
text = text.replace('"version_id": 1011040', '"version_id": 1011050')
text = text.replace('"version_string": "1.1.4"', '"version_string": "1.1.5"')
addon.write_text(text, encoding='utf-8')

reg = Path('.github/security_regression.py')
text = reg.read_text(encoding='utf-8')
text = text.replace("['warextMcVoteList', \"link('sunucular/oy'\"", "['warextMcDirectoryList', \"link('sunucular/oy'\"")
text = text.replace("require('_output/templates/public/warext_mc_server_directory.less', ['display: flex;', 'flex: 0 0 300px;', 'width: 300px;', 'height: 100px;', 'flex: 1 1 0;', 'flex: 0 0 128px;', 'aspect-ratio: 3 / 1'])", "require('_output/templates/public/warext_mc_server_directory.less', ['.warextMcDirectoryRow', 'flex: 0 0 316px;', 'width: 300px;', 'height: 100px;', 'flex: 1 1 auto;', 'flex: 0 0 138px;', 'aspect-ratio: 3 / 1'])")
text = text.replace("if version_string != '1.1.4' or int(addon.get('version_id', 0)) < 1011040:\n    raise SystemExit('Sürüm numarası 1.1.4 değil.')", "if version_string != '1.1.5' or int(addon.get('version_id', 0)) < 1011050:\n    raise SystemExit('Sürüm numarası 1.1.5 değil.')")
reg.write_text(text, encoding='utf-8')

readme = Path('README.md')
text = readme.read_text(encoding='utf-8').replace('Warext-MinecraftVote-1.1.4.zip', 'Warext-MinecraftVote-1.1.5.zip')
readme.write_text(text, encoding='utf-8')
