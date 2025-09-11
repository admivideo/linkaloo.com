package com.android.linkaloo

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.util.Log
import androidx.appcompat.app.AppCompatActivity

class ShareReceiverActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        when (intent?.action) {
            Intent.ACTION_SEND -> {
                when (intent.type) {
                    "text/plain" -> {
                        val sharedText = intent.getStringExtra(Intent.EXTRA_TEXT)
                        sharedText?.let { handleLink(it) }
                    }
                    else -> {
                        val stream = intent.getParcelableExtra<Uri>(Intent.EXTRA_STREAM)
                        stream?.toString()?.let { handleLink(it) }
                    }
                }
            }
            Intent.ACTION_VIEW -> {
                val data: Uri? = intent.data
                data?.toString()?.let { handleLink(it) }
            }
            Intent.ACTION_SEND_MULTIPLE -> {
                if ("text/plain" == intent.type) {
                    val texts = intent.getStringArrayListExtra(Intent.EXTRA_TEXT)
                    texts?.forEach { handleLink(it) }
                } else {
                    val streams = intent.getParcelableArrayListExtra<Uri>(Intent.EXTRA_STREAM)
                    streams?.forEach { handleLink(it.toString()) }
                }
            }
        }

        // Redirige a tu Main si procede o muestra una UI ligera
        finish()
    }

    private fun handleLink(link: String) {
        Log.d("ShareReceiver", "Received link: $link")
        // Abre Linkaloo con el enlace compartido como parámetro
        val encoded = Uri.encode(link)
        val uri = Uri.parse("https://linkaloo.com/?shared=" + encoded)
        startActivity(Intent(Intent.ACTION_VIEW, uri))
    }
}
