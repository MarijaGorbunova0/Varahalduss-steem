<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="UTF-8"/>
    <xsl:template match="/">
        <html>
            <head>
                <meta charset="utf-8"/>
                <title>Varade tabel</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; }
                    th { background: #f7f7f7; }
                </style>
            </head>
            <body>
                <h2>Varade tabel</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Nimetus</th>
                        <th>Seisund</th>
                        <th>Maksumus</th>
                        <th>Vastutaja</th>
                        <th>Ostukuupäev</th>
                        <th>Asukoht</th>
                        <th>Märkus</th>
                    </tr>
                    <xsl:for-each select="dim1/dim2/dim3/vara">
                        <tr>
                            <td><xsl:value-of select="id"/></td>
                            <td><xsl:value-of select="nimetus"/></td>
                            <td><xsl:value-of select="seisund"/></td>
                            <td><xsl:value-of select="maksumus"/></td>
                            <td><xsl:value-of select="vastutaja"/></td>
                            <td><xsl:value-of select="ostukuupäev"/></td>
                            <td><xsl:value-of select="asukoht"/></td>
                            <td><xsl:value-of select="markus"/></td>
                        </tr>
                    </xsl:for-each>
                </table>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
